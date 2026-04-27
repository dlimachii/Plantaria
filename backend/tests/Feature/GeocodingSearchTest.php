<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeocodingSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_geocoding_search_returns_normalized_results_and_uses_cache(): void
    {
        Cache::flush();

        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::response([
                [
                    'display_name' => 'Barcelona, Cataluna, Espana',
                    'lat' => '41.3828939',
                    'lon' => '2.1774322',
                    'type' => 'administrative',
                    'category' => 'boundary',
                ],
            ]),
        ]);

        $firstResponse = $this->getJson('/api/geocoding/search?q=Barcelona&limit=3');
        $secondResponse = $this->getJson('/api/geocoding/search?q=Barcelona&limit=3');

        $firstResponse
            ->assertOk()
            ->assertJsonPath('data.0.display_name', 'Barcelona, Cataluna, Espana')
            ->assertJsonPath('data.0.latitude', 41.3828939)
            ->assertJsonPath('data.0.longitude', 2.1774322)
            ->assertJsonPath('data.0.type', 'administrative')
            ->assertJsonPath('data.0.category', 'boundary');

        $secondResponse->assertOk();

        Http::assertSentCount(1);
    }

    public function test_geocoding_search_validates_inputs(): void
    {
        $this->getJson('/api/geocoding/search?q=a')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['q']);

        $this->getJson('/api/geocoding/search?q=Barcelona&limit=10')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['limit']);
    }
}
