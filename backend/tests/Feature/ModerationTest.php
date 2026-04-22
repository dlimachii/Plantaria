<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\PlantRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_moderator_can_verify_pending_record(): void
    {
        $moderator = User::factory()->create([
            'role' => UserRole::MOD,
        ]);

        $author = User::factory()->create();

        $record = PlantRecord::create([
            'created_by_user_id' => $author->id,
            'provisional_common_name' => 'Morera',
            'description' => 'Pendiente de validar',
            'primary_photo_path' => 'uploads/morera.jpg',
            'latitude' => 39.4699,
            'longitude' => -0.3763,
        ]);

        Sanctum::actingAs($moderator);

        $response = $this->postJson("/api/admin/moderation/records/{$record->public_id}/verify", [
            'verification_status' => 'verified',
            'verified_common_name' => 'Morera',
            'verified_scientific_name' => 'Morus alba',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.verification_status', 'verified')
            ->assertJsonPath('data.verified_scientific_name', 'Morus alba');
    }
}
