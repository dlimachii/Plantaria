<?php

namespace Tests\Feature;

use App\Models\PlantRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FlagTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_flag_record(): void
    {
        $reporter = User::factory()->create();
        $author = User::factory()->create();

        $record = PlantRecord::create([
            'created_by_user_id' => $author->id,
            'provisional_common_name' => 'Planta rara',
            'primary_photo_path' => 'uploads/planta.jpg',
            'latitude' => 39.4699,
            'longitude' => -0.3763,
        ]);

        Sanctum::actingAs($reporter);

        $response = $this->postJson('/api/flags', [
            'target_type' => 'record',
            'target_reference' => $record->public_id,
            'reason' => 'Contenido dudoso en esta ubicacion',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.target_type', 'record')
            ->assertJsonPath('data.status', 'open');
    }
}
