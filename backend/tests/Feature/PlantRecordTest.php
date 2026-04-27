<?php

namespace Tests\Feature;

use App\Models\PlantRecord;
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

    public function test_records_can_be_filtered_by_radius(): void
    {
        $user = User::factory()->create();

        $nearRecord = PlantRecord::create([
            'created_by_user_id' => $user->id,
            'provisional_common_name' => 'Jacaranda',
            'primary_photo_path' => 'uploads/jacaranda.jpg',
            'latitude' => 41.3851,
            'longitude' => 2.1734,
            'latest_observation_at' => now(),
        ]);

        PlantRecord::create([
            'created_by_user_id' => $user->id,
            'provisional_common_name' => 'Madroño',
            'primary_photo_path' => 'uploads/madrono.jpg',
            'latitude' => 40.4168,
            'longitude' => -3.7038,
            'latest_observation_at' => now(),
        ]);

        $response = $this->getJson('/api/records?latitude=41.3851&longitude=2.1734&radius_km=5&limit=10');

        $response->assertOk();

        $records = $response->json('data');

        $this->assertCount(1, $records);
        $this->assertSame($nearRecord->public_id, $records[0]['public_id']);
        $this->assertArrayHasKey('distance_km', $records[0]);
        $this->assertLessThan(5, $records[0]['distance_km']);
    }

    public function test_record_listing_validates_filters(): void
    {
        $this->getJson('/api/records?limit=500')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['limit']);

        $this->getJson('/api/records?latitude=41.3851')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['longitude', 'radius_km']);
    }

    public function test_record_search_matches_plant_names_not_public_id(): void
    {
        $user = User::factory()->create();

        PlantRecord::create([
            'public_id' => 'SEARCH-ID-ONLY',
            'created_by_user_id' => $user->id,
            'provisional_common_name' => 'Rosa silvestre',
            'verified_scientific_name' => 'Rosa canina',
            'primary_photo_path' => 'uploads/rosa.jpg',
            'latitude' => 41.3851,
            'longitude' => 2.1734,
            'latest_observation_at' => now(),
        ]);

        $nameResponse = $this->getJson('/api/records?q=Rosa');
        $nameResponse
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $idResponse = $this->getJson('/api/records?q=SEARCH-ID-ONLY');
        $idResponse
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_record_and_observation_default_to_unknown_condition(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $recordResponse = $this->postJson('/api/records', [
            'provisional_common_name' => 'Amapola',
            'primary_photo_path' => 'uploads/amapola-1.jpg',
            'latitude' => 41.3874,
            'longitude' => 2.1686,
        ]);

        $recordResponse
            ->assertCreated()
            ->assertJsonPath('data.plant_condition', 'unknown')
            ->assertJsonPath('data.observations.0.plant_condition', 'unknown');

        $publicId = $recordResponse->json('data.public_id');

        $this->postJson("/api/records/{$publicId}/observations", [
            'photo_path' => 'uploads/amapola-2.jpg',
            'latitude' => 41.388,
            'longitude' => 2.169,
        ])
            ->assertCreated()
            ->assertJsonPath('data.plant_condition', 'unknown');
    }
}
