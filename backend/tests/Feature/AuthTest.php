<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_and_receive_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'handle' => 'plantaria1',
            'display_name' => 'Plantaria Demo',
            'email' => 'demo@example.com',
            'password' => 'Password1',
            'password_confirmation' => 'Password1',
            'country' => 'Espana',
            'province' => 'Valencia',
            'city' => 'Valencia',
            'birthdate' => '2000-01-01',
            'device_name' => 'phpunit',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('user.handle', 'plantaria1')
            ->assertJsonStructure([
                'token',
                'user' => ['uid', 'handle', 'display_name', 'role', 'status'],
            ]);
    }

    public function test_banned_user_cannot_login(): void
    {
        User::factory()->create([
            'handle' => 'blocked',
            'password' => 'Password1',
            'status' => UserStatus::BANNED,
        ]);

        $response = $this->postJson('/api/auth/login', [
            'handle' => 'blocked',
            'password' => 'Password1',
            'device_name' => 'phpunit',
        ]);

        $response
            ->assertForbidden()
            ->assertJsonPath('message', 'La cuenta está bloqueada.');
    }

    public function test_existing_token_from_banned_user_cannot_use_authenticated_api(): void
    {
        $user = User::factory()->create([
            'status' => UserStatus::BANNED,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/auth/me')
            ->assertForbidden()
            ->assertJsonPath('message', 'Cuenta no activa.');
    }
}
