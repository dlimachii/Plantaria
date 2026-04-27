<?php

namespace Tests\Feature;

use App\Enums\FlagStatus;
use App\Enums\PlantCondition;
use App\Enums\FlagTargetType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Models\ModerationFlag;
use App\Models\PlantRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_and_view_dashboard(): void
    {
        User::factory()->create([
            'handle' => 'plantaria_admin',
            'email' => 'admin@plantaria.local',
            'password' => Hash::make('PlantariaAdmin1'),
            'role' => UserRole::ADMIN,
        ]);

        $response = $this->post('/admin/login', [
            'login' => 'plantaria_admin',
            'password' => 'PlantariaAdmin1',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Resumen operativo')
            ->assertSee('Actividad diaria')
            ->assertSee('Top búsquedas');
    }

    public function test_regular_user_cannot_view_admin_dashboard(): void
    {
        $this->actingAs(User::factory()->create([
            'role' => UserRole::USER,
        ]));

        $this->get('/admin')->assertForbidden();
    }

    public function test_moderator_can_verify_record_from_web_panel(): void
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

        $this->actingAs($moderator);

        $this->get('/admin/moderation/pending')
            ->assertOk()
            ->assertSee('Morera');

        $this->post("/admin/moderation/records/{$record->public_id}/verify", [
            'verified_common_name' => 'Morera',
            'verified_scientific_name' => 'Morus alba',
            'description' => 'Ficha validada desde panel.',
        ])->assertRedirect(route('admin.moderation.show', $record->public_id));

        $record->refresh();

        $this->assertSame(VerificationStatus::VERIFIED, $record->verification_status);
        $this->assertSame('Morus alba', $record->verified_scientific_name);
        $this->assertSame($moderator->id, $record->verified_by_user_id);
    }

    public function test_admin_can_update_record_from_web_panel(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);
        $author = User::factory()->create();
        $record = PlantRecord::create([
            'created_by_user_id' => $author->id,
            'provisional_common_name' => 'Planta por revisar',
            'description' => 'Texto inicial',
            'primary_photo_path' => 'uploads/inicial.jpg',
            'plant_condition' => PlantCondition::UNKNOWN,
            'latitude' => 39.4699,
            'longitude' => -0.3763,
        ]);

        $this->actingAs($admin);

        $this->post("/admin/moderation/records/{$record->public_id}", [
            'provisional_common_name' => 'Morera blanca',
            'verified_common_name' => 'Morera',
            'verified_scientific_name' => 'Morus alba',
            'description' => 'Registro corregido desde panel.',
            'primary_photo_path' => 'uploads/morera-editada.jpg',
            'plant_condition' => PlantCondition::GOOD->value,
            'verification_status' => VerificationStatus::VERIFIED->value,
            'latitude' => 41.4036,
            'longitude' => 2.1744,
        ])->assertRedirect(route('admin.moderation.show', $record->public_id));

        $record->refresh();

        $this->assertSame('Morera blanca', $record->provisional_common_name);
        $this->assertSame('Morus alba', $record->verified_scientific_name);
        $this->assertSame(PlantCondition::GOOD, $record->plant_condition);
        $this->assertSame(VerificationStatus::VERIFIED, $record->verification_status);
        $this->assertSame($admin->id, $record->verified_by_user_id);
        $this->assertSame('uploads/morera-editada.jpg', $record->primary_photo_path);
        $this->assertEqualsWithDelta(41.4036, (float) $record->latitude, 0.0000001);
        $this->assertEqualsWithDelta(2.1744, (float) $record->longitude, 0.0000001);
    }

    public function test_moderator_cannot_use_admin_record_update_form(): void
    {
        $moderator = User::factory()->create([
            'role' => UserRole::MOD,
        ]);
        $author = User::factory()->create();
        $record = PlantRecord::create([
            'created_by_user_id' => $author->id,
            'provisional_common_name' => 'Planta cerrada',
            'primary_photo_path' => 'uploads/planta.jpg',
            'latitude' => 39.4699,
            'longitude' => -0.3763,
        ]);

        $this->actingAs($moderator);

        $this->post("/admin/moderation/records/{$record->public_id}", [
            'provisional_common_name' => 'Cambio no permitido',
            'verified_common_name' => 'Cambio',
            'verified_scientific_name' => 'Cambio scientifico',
            'description' => 'No deberia guardarse.',
            'primary_photo_path' => 'uploads/cambio.jpg',
            'plant_condition' => PlantCondition::GOOD->value,
            'verification_status' => VerificationStatus::VERIFIED->value,
            'latitude' => 41.4036,
            'longitude' => 2.1744,
        ])->assertForbidden();

        $record->refresh();

        $this->assertSame('Planta cerrada', $record->provisional_common_name);
        $this->assertSame(VerificationStatus::PENDING, $record->verification_status);
    }

    public function test_moderation_list_can_filter_records_by_status_and_search(): void
    {
        $moderator = User::factory()->create([
            'role' => UserRole::MOD,
        ]);
        $author = User::factory()->create();

        PlantRecord::create([
            'created_by_user_id' => $author->id,
            'provisional_common_name' => 'Pendiente visible',
            'primary_photo_path' => 'uploads/pending.jpg',
            'latitude' => 39.4699,
            'longitude' => -0.3763,
        ]);

        PlantRecord::create([
            'created_by_user_id' => $author->id,
            'provisional_common_name' => 'Morera blanca',
            'verified_common_name' => 'Morera',
            'verified_scientific_name' => 'Morus alba',
            'verification_status' => VerificationStatus::VERIFIED,
            'verified_by_user_id' => $moderator->id,
            'verified_at' => now(),
            'primary_photo_path' => 'uploads/verified.jpg',
            'latitude' => 41.4036,
            'longitude' => 2.1744,
        ]);

        $this->actingAs($moderator);

        $this->get('/admin/moderation/pending?status=verified&q=Morus')
            ->assertOk()
            ->assertSee('Morera')
            ->assertDontSee('Pendiente visible');
    }

    public function test_moderator_can_update_flag_status_from_web_panel(): void
    {
        $moderator = User::factory()->create([
            'role' => UserRole::MOD,
        ]);
        $reporter = User::factory()->create();
        $author = User::factory()->create();
        $record = PlantRecord::create([
            'created_by_user_id' => $author->id,
            'provisional_common_name' => 'Planta dudosa',
            'primary_photo_path' => 'uploads/planta.jpg',
            'latitude' => 39.4699,
            'longitude' => -0.3763,
        ]);
        $flag = ModerationFlag::create([
            'target_type' => FlagTargetType::RECORD,
            'target_id' => $record->id,
            'created_by_user_id' => $reporter->id,
            'reason' => 'Contenido dudoso',
        ]);

        $this->actingAs($moderator);

        $this->get('/admin/flags')
            ->assertOk()
            ->assertSee('Contenido dudoso');

        $this->post("/admin/flags/{$flag->uid}", [
            'status' => 'resolved',
        ])->assertRedirect();

        $flag->refresh();

        $this->assertSame(FlagStatus::RESOLVED, $flag->status);
        $this->assertSame($moderator->id, $flag->resolved_by_user_id);
    }

    public function test_admin_can_update_user_from_web_panel(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);
        $user = User::factory()->create([
            'handle' => 'plantaria_user',
            'role' => UserRole::USER,
            'status' => UserStatus::ACTIVE,
        ]);

        $this->actingAs($admin);

        $this->get('/admin/users')
            ->assertOk()
            ->assertSee('plantaria_user');

        $this->post('/admin/users/plantaria_user', [
            'display_name' => 'Usuario Moderador',
            'role' => 'mod',
            'status' => 'active',
            'country' => 'Espana',
            'province' => 'Barcelona',
            'city' => 'Barcelona',
        ])->assertRedirect(route('admin.users.show', 'plantaria_user'));

        $user->refresh();

        $this->assertSame(UserRole::MOD, $user->role);
        $this->assertSame('Usuario Moderador', $user->display_name);
    }
}
