<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\PlantRecord;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_demo_records_with_demo_images(): void
    {
        Storage::fake('public');

        $this->seed(DatabaseSeeder::class);

        $record = PlantRecord::query()
            ->where('public_id', 'PLANTARIADEMOBCN000001')
            ->firstOrFail();

        $this->assertSame('demo/platanero-ciutadella.png', $record->primary_photo_path);
        Storage::disk('public')->assertExists($record->primary_photo_path);

        $image = Storage::disk('public')->get($record->primary_photo_path);

        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $image);
    }

    public function test_database_seeder_creates_demo_users_for_each_role(): void
    {
        Storage::fake('public');

        $this->seed(DatabaseSeeder::class);

        $this->assertDemoUser('plantaria_user', (string) env('PLANTARIA_USER_PASSWORD'), UserRole::USER);
        $this->assertDemoUser('plantaria_mod', (string) env('PLANTARIA_MOD_PASSWORD'), UserRole::MOD);
        $this->assertDemoUser('plantaria_admin', (string) env('PLANTARIA_ADMIN_PASSWORD'), UserRole::ADMIN);
    }

    private function assertDemoUser(string $handle, string $password, UserRole $role): void
    {
        $user = User::query()
            ->where('handle', $handle)
            ->firstOrFail();

        $this->assertSame($role, $user->role);
        $this->assertTrue(Hash::check($password, $user->password));
    }
}
