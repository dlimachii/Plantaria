<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
