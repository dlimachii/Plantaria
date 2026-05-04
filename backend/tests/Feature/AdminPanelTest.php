<?php

namespace Tests\Feature;

use App\Enums\FlagStatus;
use App\Enums\FlagTargetType;
use App\Enums\ObservationSourceType;
use App\Enums\PlantCondition;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Enums\VerificationStatus;
use App\Models\ModerationFlag;
use App\Models\Observation;
use App\Models\PlantRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
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

    public function test_flags_panel_shows_target_context_and_can_filter_by_type_and_search(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);
        $reporter = User::factory()->create();
        $author = User::factory()->create();
        $record = PlantRecord::create([
            'created_by_user_id' => $author->id,
            'provisional_common_name' => 'Lavanda senalada',
            'primary_photo_path' => 'uploads/lavanda.jpg',
            'latitude' => 41.3874,
            'longitude' => 2.1686,
        ]);
        $reportedUser = User::factory()->create([
            'handle' => 'usuario_reportado',
            'display_name' => 'Usuario Reportado',
        ]);

        ModerationFlag::create([
            'target_type' => FlagTargetType::RECORD,
            'target_id' => $record->id,
            'created_by_user_id' => $reporter->id,
            'reason' => 'Foto incorrecta para esta planta',
        ]);
        ModerationFlag::create([
            'target_type' => FlagTargetType::USER,
            'target_id' => $reportedUser->id,
            'created_by_user_id' => $reporter->id,
            'reason' => 'Spam de perfil',
        ]);

        $this->actingAs($admin);

        $this->get('/admin/flags?target_type=record&q=Foto')
            ->assertOk()
            ->assertSee($record->public_id)
            ->assertSee('Lavanda senalada')
            ->assertSee('Foto incorrecta para esta planta')
            ->assertDontSee('Spam de perfil');
    }

    public function test_record_detail_shows_related_flags_for_record_and_observations(): void
    {
        $moderator = User::factory()->create([
            'role' => UserRole::MOD,
        ]);
        $reporter = User::factory()->create();
        $author = User::factory()->create();
        $record = PlantRecord::create([
            'created_by_user_id' => $author->id,
            'provisional_common_name' => 'Romero con dudas',
            'primary_photo_path' => 'uploads/romero.jpg',
            'latitude' => 41.3874,
            'longitude' => 2.1686,
        ]);
        $observation = Observation::create([
            'plant_record_id' => $record->id,
            'author_user_id' => $author->id,
            'photo_path' => 'uploads/romero-observacion.jpg',
            'note' => 'Observacion duplicada en el mismo punto',
            'latitude' => 41.3875,
            'longitude' => 2.1687,
            'observed_at' => now(),
        ]);
        $otherRecord = PlantRecord::create([
            'created_by_user_id' => $author->id,
            'provisional_common_name' => 'Otro registro',
            'primary_photo_path' => 'uploads/otro.jpg',
            'latitude' => 41.4,
            'longitude' => 2.17,
        ]);

        ModerationFlag::create([
            'target_type' => FlagTargetType::RECORD,
            'target_id' => $record->id,
            'created_by_user_id' => $reporter->id,
            'reason' => 'Foto principal borrosa',
        ]);
        ModerationFlag::create([
            'target_type' => FlagTargetType::OBSERVATION,
            'target_id' => $observation->id,
            'created_by_user_id' => $reporter->id,
            'reason' => 'Observacion fuera de lugar',
        ]);
        ModerationFlag::create([
            'target_type' => FlagTargetType::RECORD,
            'target_id' => $otherRecord->id,
            'created_by_user_id' => $reporter->id,
            'reason' => 'No debe verse aqui',
        ]);

        $this->actingAs($moderator);

        $this->get("/admin/moderation/records/{$record->public_id}")
            ->assertOk()
            ->assertSee('Flags relacionados')
            ->assertSee('Foto principal borrosa')
            ->assertSee('Observacion fuera de lugar')
            ->assertDontSee('No debe verse aqui');
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

    public function test_dashboard_shows_pandas_snapshot_when_available(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $this->writePandasSnapshot();

        try {
            $this->actingAs($admin);

            $this->get('/admin')
                ->assertOk()
                ->assertSee('Analitica Python + pandas')
                ->assertSee('Eventos en 7 dias')
                ->assertSee('2 registros siguen pendientes de revision');
        } finally {
            File::deleteDirectory(storage_path('app/analytics'));
        }
    }

    public function test_admin_assistant_can_query_ollama_with_pandas_context(): void
    {
        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);

        $this->writePandasSnapshot();
        Http::fake([
            '*' => Http::response(['response' => 'Revisa primero los registros pendientes.'], 200),
        ]);

        try {
            $this->actingAs($admin);

            $this->post('/admin/assistant', [
                'question' => 'Que reviso antes de la demo?',
            ])
                ->assertOk()
                ->assertSee('Revisa primero los registros pendientes.');
        } finally {
            File::deleteDirectory(storage_path('app/analytics'));
        }
    }

    public function test_admin_assistant_answers_direct_database_questions_without_pandas_snapshot(): void
    {
        File::deleteDirectory(storage_path('app/analytics'));

        $admin = User::factory()->create([
            'role' => UserRole::ADMIN,
        ]);
        $topObserver = User::factory()->create([
            'handle' => 'botanica_alpha',
            'display_name' => 'Botanica Alpha',
        ]);
        $otherObserver = User::factory()->create([
            'handle' => 'botanica_beta',
            'display_name' => 'Botanica Beta',
        ]);

        $recordWithoutScientificName = PlantRecord::create([
            'created_by_user_id' => $topObserver->id,
            'provisional_common_name' => 'Menta pendiente',
            'verified_common_name' => 'Menta',
            'verified_scientific_name' => null,
            'verification_status' => VerificationStatus::VERIFIED,
            'verified_by_user_id' => $admin->id,
            'verified_at' => now(),
            'primary_photo_path' => 'uploads/menta.jpg',
            'latitude' => 41.3874,
            'longitude' => 2.1686,
        ]);
        $completeRecord = PlantRecord::create([
            'created_by_user_id' => $otherObserver->id,
            'provisional_common_name' => 'Tomillo',
            'verified_common_name' => 'Tomillo',
            'verified_scientific_name' => 'Thymus vulgaris',
            'verification_status' => VerificationStatus::VERIFIED,
            'verified_by_user_id' => $admin->id,
            'verified_at' => now(),
            'primary_photo_path' => 'uploads/tomillo.jpg',
            'latitude' => 41.4026,
            'longitude' => 2.1744,
        ]);
        $pendingRecord = PlantRecord::create([
            'created_by_user_id' => $otherObserver->id,
            'provisional_common_name' => 'Calendula',
            'verified_scientific_name' => null,
            'verification_status' => VerificationStatus::PENDING,
            'primary_photo_path' => 'uploads/calendula.jpg',
            'latitude' => 41.3910,
            'longitude' => 2.1710,
        ]);

        foreach ([$recordWithoutScientificName, $completeRecord] as $index => $record) {
            Observation::create([
                'plant_record_id' => $record->id,
                'author_user_id' => $topObserver->id,
                'photo_path' => 'uploads/obs-alpha-'.$index.'.jpg',
                'plant_condition' => PlantCondition::GOOD,
                'latitude' => $record->latitude,
                'longitude' => $record->longitude,
                'source_type' => ObservationSourceType::UPDATE,
                'observed_at' => now()->subMinutes($index + 1),
            ]);
        }

        Observation::create([
            'plant_record_id' => $pendingRecord->id,
            'author_user_id' => $otherObserver->id,
            'photo_path' => 'uploads/obs-beta.jpg',
            'plant_condition' => PlantCondition::GOOD,
            'latitude' => $pendingRecord->latitude,
            'longitude' => $pendingRecord->longitude,
            'source_type' => ObservationSourceType::UPDATE,
            'observed_at' => now(),
        ]);

        $this->actingAs($admin);

        $this->post('/admin/assistant', [
            'question' => 'Que usuarios han creado mas observaciones? Que plantas verificadas no tienen nombre cientifico?',
        ])
            ->assertOk()
            ->assertSee('Consulta directa a BBDD segura de Plantaria')
            ->assertSee('Botanica Alpha (@botanica_alpha): 2 observaciones; 2 de seguimiento.')
            ->assertSee('Plantas verificadas sin nombre cientifico')
            ->assertSee($recordWithoutScientificName->public_id)
            ->assertDontSee($completeRecord->public_id)
            ->assertDontSee($pendingRecord->public_id)
            ->assertDontSee('No he reconocido una consulta directa segura');
    }

    public function test_moderator_cannot_use_admin_assistant(): void
    {
        $moderator = User::factory()->create([
            'role' => UserRole::MOD,
        ]);

        $this->actingAs($moderator);

        $this->get('/admin/assistant')->assertForbidden();
    }

    public function test_analytics_command_exports_datasets_without_running_python(): void
    {
        $author = User::factory()->create([
            'handle' => 'analytics_user',
        ]);

        PlantRecord::create([
            'created_by_user_id' => $author->id,
            'provisional_common_name' => 'Lavanda analytics',
            'primary_photo_path' => 'uploads/lavanda.jpg',
            'latitude' => 41.3874,
            'longitude' => 2.1686,
        ]);

        try {
            $this->artisan('plantaria:analytics:build --skip-python')
                ->assertSuccessful();

            $this->assertFileExists(storage_path('app/analytics/input/users.csv'));
            $this->assertFileExists(storage_path('app/analytics/input/plant_records.csv'));
            $this->assertStringContainsString(
                'analytics_user',
                File::get(storage_path('app/analytics/input/users.csv')),
            );
        } finally {
            File::deleteDirectory(storage_path('app/analytics'));
        }
    }

    private function writePandasSnapshot(): void
    {
        File::ensureDirectoryExists(storage_path('app/analytics/output'));
        File::put(storage_path('app/analytics/output/admin_dashboard.json'), json_encode([
            'generated_at' => '2026-04-28T15:24:00+00:00',
            'source_counts' => [
                'users' => 3,
                'plant_records' => 4,
                'observations' => 5,
                'moderation_flags' => 1,
                'app_events' => 9,
            ],
            'kpis' => [
                'events_7d' => 9,
                'reports_7d' => 2,
                'observations_7d' => 3,
                'pending_records' => 2,
                'open_flags' => 1,
                'verification_rate' => 50.0,
            ],
            'risk_signals' => [
                '2 registros siguen pendientes de revision.',
            ],
            'top_searches' => [
                ['search_query' => 'Lavanda', 'search_type' => 'text', 'total' => 4],
            ],
            'top_creators' => [
                ['handle' => 'plantaria_demo', 'display_name' => 'Plantaria Demo', 'records_count' => 2, 'observations_count' => 3],
            ],
        ], JSON_THROW_ON_ERROR));
    }
}
