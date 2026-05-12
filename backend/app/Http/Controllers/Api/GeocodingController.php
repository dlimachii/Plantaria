<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventType;
use App\Http\Controllers\Controller;
use App\Models\AppEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class GeocodingController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $query = trim($validated['q']);
        $limit = (int) ($validated['limit'] ?? 5);

        AppEvent::record(
            EventType::MAP_SEARCH,
            searchQuery: $query,
            searchType: 'place'
        );

        try {
            $results = Cache::remember(
                sprintf('nominatim-search:%s:%d', md5($query), $limit),
                now()->addMinutes(30),
                fn (): array => $this->searchNominatim($query, $limit),
            );
        } catch (Throwable $throwable) {
            report($throwable);

            return response()->json([
                'message' => 'No se pudo resolver la ubicación ahora mismo.',
            ], 502);
        }

        return response()->json([
            'data' => $results,
        ]);
    }

    private function searchNominatim(string $query, int $limit): array
    {
        $response = Http::baseUrl(rtrim(config('services.nominatim.base_url'), '/'))
            ->acceptJson()
            ->timeout(10)
            ->withHeaders([
                'User-Agent' => config('services.nominatim.user_agent'),
            ])
            ->get('/search', [
                'q' => $query,
                'format' => 'jsonv2',
                'addressdetails' => 1,
                'limit' => $limit,
                'dedupe' => 1,
            ])
            ->throw();

        return collect($response->json())
            ->map(function (array $result): ?array {
                if (! isset($result['lat'], $result['lon'])) {
                    return null;
                }

                return [
                    'display_name' => $result['display_name'] ?? 'Ubicación sin nombre',
                    'latitude' => (float) $result['lat'],
                    'longitude' => (float) $result['lon'],
                    'type' => $result['type'] ?? null,
                    'category' => $result['category'] ?? null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
