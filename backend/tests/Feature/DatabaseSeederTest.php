<?php

namespace Tests\Feature;

use App\Models\PlantRecord;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
