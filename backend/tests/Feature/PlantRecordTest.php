<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlantRecordTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_record_and_observation(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $recordResponse = $this->postJson('/api/records', [
            'provisional_common_name' => 'Morera',
            'description' => 'Fruto aun no maduro',
            'primary_photo_path' => 'uploads/morera-1.jpg',
            'plant_condition' => 'good',
            'latitude' => 39.4699,
            'longitude' => -0.3763,
        ]);

        $recordResponse
            ->assertCreated()
            ->assertJsonPath('data.provisional_common_name', 'Morera')
            ->assertJsonPath('data.primary_photo_path', 'uploads/morera-1.jpg')
            ->assertJsonPath('data.primary_photo_url', asset('storage/uploads/morera-1.jpg'));

        $publicId = $recordResponse->json('data.public_id');

        $observationResponse = $this->postJson("/api/records/{$publicId}/observations", [
            'photo_path' => 'uploads/morera-2.jpg',
            'note' => 'Moras ya maduras',
            'plant_condition' => 'good',
            'latitude' => 39.4699,
            'longitude' => -0.3763,
        ]);

        $observationResponse
            ->assertCreated()
            ->assertJsonPath('data.record_public_id', $publicId)
            ->assertJsonPath('data.photo_url', asset('storage/uploads/morera-2.jpg'));

        $detailResponse = $this->getJson("/api/records/{$publicId}");

        $detailResponse
            ->assertOk()
            ->assertJsonPath('data.primary_photo_url', asset('storage/uploads/morera-1.jpg'))
            ->assertJsonFragment(['photo_url' => asset('storage/uploads/morera-1.jpg')])
            ->assertJsonFragment(['photo_url' => asset('storage/uploads/morera-2.jpg')]);
    }
}
