<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_preflight_uses_configured_cors_origin_instead_of_wildcard(): void
    {
        config([
            'cors.allowed_origins' => ['https://dlimachii.com'],
            'cors.supports_credentials' => false,
        ]);

        $response = $this->withHeaders([
            'Origin' => 'https://dlimachii.com',
            'Access-Control-Request-Method' => 'GET',
        ])->call('OPTIONS', '/api/records');

        $response->assertSuccessful();
        $response->assertHeader('Access-Control-Allow-Origin', 'https://dlimachii.com');
        $this->assertNotSame('*', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function test_admin_login_is_rate_limited_after_five_attempts_per_minute(): void
    {
        $password = 'RateLimitPass1!';

        User::factory()->create([
            'handle' => 'rate_limit_admin',
            'email' => 'rate-limit-admin@plantaria.local',
            'password' => Hash::make($password),
            'role' => UserRole::ADMIN,
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
                ->post('/admin/login', [
                    'login' => 'rate_limit_admin',
                    'password' => $password,
                ])
                ->assertRedirect(route('admin.dashboard'));

            auth()->logout();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->post('/admin/login', [
                'login' => 'rate_limit_admin',
                'password' => $password,
            ])
            ->assertStatus(429);
    }
}
