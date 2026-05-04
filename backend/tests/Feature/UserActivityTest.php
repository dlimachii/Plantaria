<?php

namespace Tests\Feature;

use App\Enums\FlagTargetType;
use App\Enums\ObservationSourceType;
use App\Enums\PlantCondition;
use App\Enums\UserRole;
use App\Models\ModerationFlag;
use App\Models\Observation;
use App\Models\PlantRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_user_activity_is_empty(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/me/activity')
            ->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_user_activity_contains_only_own_reports_observations_and_flags(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownRecord = PlantRecord::create([
            'created_by_user_id' => $user->id,
            'provisional_common_name' => 'Lavanda propia',
            'primary_photo_path' => 'uploads/lavanda.jpg',
            'latitude' => 41.3874,
            'longitude' => 2.1686,
            'latest_observation_at' => now(),
        ]);

        Observation::create([
            'plant_record_id' => $ownRecord->id,
            'author_user_id' => $user->id,
            'photo_path' => 'uploads/lavanda-observacion.jpg',
            'note' => 'Nueva floracion',
            'plant_condition' => PlantCondition::GOOD,
            'latitude' => 41.3874,
            'longitude' => 2.1686,
            'source_type' => ObservationSourceType::UPDATE,
            'observed_at' => now()->addMinute(),
        ]);

        $otherRecord = PlantRecord::create([
            'created_by_user_id' => $otherUser->id,
            'provisional_common_name' => 'Romero ajeno',
            'primary_photo_path' => 'uploads/romero.jpg',
            'latitude' => 41.4,
            'longitude' => 2.17,
            'latest_observation_at' => now(),
        ]);

        ModerationFlag::create([
            'target_type' => FlagTargetType::RECORD,
            'target_id' => $otherRecord->id,
            'created_by_user_id' => $user->id,
            'reason' => 'Ubicacion dudosa',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/me/activity');

        $response
            ->assertOk()
            ->assertJsonFragment(['type' => 'record_created'])
            ->assertJsonFragment(['type' => 'observation_created'])
            ->assertJsonFragment(['type' => 'flag_created'])
            ->assertJsonFragment(['description' => 'Lavanda propia'])
            ->assertJsonMissing(['description' => 'Romero ajeno']);

        $this->assertCount(3, $response->json('data'));
    }

    public function test_moderator_activity_contains_moderation_actions(): void
    {
        $moderator = User::factory()->create([
            'role' => UserRole::MOD,
        ]);
        $author = User::factory()->create();

        $record = PlantRecord::create([
            'created_by_user_id' => $author->id,
            'provisional_common_name' => 'Bugambilia pendiente',
            'primary_photo_path' => 'uploads/bugambilia.jpg',
            'latitude' => 41.4026,
            'longitude' => 2.1595,
            'latest_observation_at' => now(),
        ]);

        Sanctum::actingAs($moderator);

        $this->postJson("/api/admin/moderation/records/{$record->public_id}/verify", [
            'verification_status' => 'verified',
            'verified_common_name' => 'Bugambilia',
            'verified_scientific_name' => 'Bougainvillea glabra',
        ])->assertOk();

        $this->getJson('/api/me/activity')
            ->assertOk()
            ->assertJsonFragment(['type' => 'record_verified'])
            ->assertJsonFragment(['label' => 'Registro verificado'])
            ->assertJsonFragment(['record_public_id' => $record->public_id]);
    }

    public function test_admin_activity_contains_user_management_actions(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);
        $target = User::factory()->create([
            'handle' => 'managed_user',
        ]);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/users/{$target->handle}", [
            'display_name' => 'Usuario gestionado',
        ])->assertOk();

        $this->getJson('/api/me/activity')
            ->assertOk()
            ->assertJsonFragment(['type' => 'user_updated'])
            ->assertJsonFragment(['label' => 'Usuario actualizado'])
            ->assertJsonFragment(['description' => '@managed_user']);
    }
}
