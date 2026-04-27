<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_cannot_access_admin_analytics_api(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/admin/analytics/summary')
            ->assertForbidden()
            ->assertJsonPath('message', 'Solo administracion.');
    }

    public function test_moderator_cannot_access_admin_user_management_api(): void
    {
        Sanctum::actingAs(User::factory()->create([
            'role' => UserRole::MOD,
        ]));

        $this->getJson('/api/admin/users')
            ->assertForbidden()
            ->assertJsonPath('message', 'Solo administracion.');
    }

    public function test_admin_can_access_admin_user_management_api(): void
    {
        Sanctum::actingAs(User::factory()->create([
            'role' => UserRole::ADMIN,
        ]));

        $this->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonStructure([
                'data',
            ]);
    }
}
