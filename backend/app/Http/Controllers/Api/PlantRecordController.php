<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventType;
use App\Enums\ObservationSourceType;
use App\Enums\PlantCondition;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlantRecordRequest;
use App\Models\AppEvent;
use App\Models\Observation;
use App\Models\PlantRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PlantRecordController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(VerificationStatus::class)],
            'q' => ['nullable', 'string', 'max:120'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'required_with:longitude,radius_km'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'required_with:latitude,radius_km'],
            'radius_km' => ['nullable', 'numeric', 'min:0.1', 'max:100', 'required_with:latitude,longitude'],
        ]);

        $query = PlantRecord::query()
            ->with(['author', 'observations'])
            ->whereNull('deleted_at');

        if (isset($validated['status'])) {
            $status = $validated['status'] instanceof VerificationStatus
                ? $validated['status']->value
                : $validated['status'];

            $query->where('verification_status', $status);
        }

        if (isset($validated['q']) && trim($validated['q']) !== '') {
            $term = trim($validated['q']);

            $query->where(function ($builder) use ($term): void {
                $builder
                    ->where('provisional_common_name', 'like', "%{$term}%")
                    ->orWhere('verified_common_name', 'like', "%{$term}%")
                    ->orWhere('verified_scientific_name', 'like', "%{$term}%");
            });

            AppEvent::record(
                EventType::MAP_SEARCH,
                auth()->user(),
                searchQuery: $term,
                searchType: 'text'
            );
        }

        $nearbySearch = isset($validated['latitude'], $validated['longitude'], $validated['radius_km']);
        $usesDatabaseDistance = false;
        $latitude = null;
        $longitude = null;
        $radiusKm = null;

        if ($nearbySearch) {
            $latitude = (float) $validated['latitude'];
            $longitude = (float) $validated['longitude'];
            $radiusKm = (float) $validated['radius_km'];
            $usesDatabaseDistance = $this->applyNearbyFilter($query, $latitude, $longitude, $radiusKm);

            AppEvent::record(
                EventType::MAP_SEARCH,
                auth()->user(),
                searchQuery: sprintf('%.7F,%.7F;%skm', $latitude, $longitude, $radiusKm),
                searchType: 'nearby'
            );
        }

        $limit = (int) ($validated['limit'] ?? 20);
        $candidateLimit = $nearbySearch && ! $usesDatabaseDistance ? min($limit * 5, 500) : $limit;

        $records = $query
            ->orderByRaw("CASE WHEN verification_status = ? THEN 0 ELSE 1 END", [VerificationStatus::VERIFIED->value])
            ->when($usesDatabaseDistance, fn (Builder $builder) => $builder->orderBy('distance_km'))
            ->orderByDesc('latest_observation_at')
            ->orderByDesc('created_at')
            ->limit($candidateLimit)
            ->get();

        if ($nearbySearch && ! $usesDatabaseDistance) {
            $records = $this->filterNearbyInMemory($records, $latitude, $longitude, $radiusKm, $limit);
        }

        return response()->json([
            'data' => $records->map(fn (PlantRecord $record) => $this->recordPayload($record)),
        ]);
    }

    public function show(string $publicId): JsonResponse
    {
        $record = PlantRecord::query()
            ->with(['author', 'verifier', 'observations.author'])
            ->where('public_id', $publicId)
            ->firstOrFail();

        AppEvent::record(EventType::RECORD_VIEWED, auth()->user(), $record);

        return response()->json([
            'data' => $this->recordPayload($record, true),
        ]);
    }

    public function store(StorePlantRecordRequest $request): JsonResponse
    {
        $user = $request->user();

        $record = DB::transaction(function () use ($request, $user): PlantRecord {
            $plantCondition = $request->input('plant_condition', PlantCondition::UNKNOWN->value);

            $record = PlantRecord::create([
                'created_by_user_id' => $user->id,
                'provisional_common_name' => $request->string('provisional_common_name')->value(),
                'description' => $request->input('description'),
                'primary_photo_path' => $request->string('primary_photo_path')->value(),
                'plant_condition' => $plantCondition,
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'latest_observation_at' => now(),
            ]);

            Observation::create([
                'plant_record_id' => $record->id,
                'author_user_id' => $user->id,
                'photo_path' => $request->string('primary_photo_path')->value(),
                'note' => $request->input('description'),
                'plant_condition' => $plantCondition,
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'source_type' => ObservationSourceType::INITIAL,
                'observed_at' => now(),
            ]);

            return $record;
        });

        $record->load(['author', 'observations']);

        AppEvent::record(EventType::RECORD_CREATED, $user, $record);

        return response()->json([
            'data' => $this->recordPayload($record, true),
        ], 201);
    }

    private function recordPayload(PlantRecord $record, bool $withObservations = false): array
    {
        $payload = [
            'uid' => $record->uid,
            'public_id' => $record->public_id,
            'provisional_common_name' => $record->provisional_common_name,
            'verified_common_name' => $record->verified_common_name,
            'verified_scientific_name' => $record->verified_scientific_name,
            'display_name' => $record->verified_common_name ?? $record->provisional_common_name,
            'description' => $record->description,
            'primary_photo_path' => $record->primary_photo_path,
            'primary_photo_url' => $this->publicStorageUrl($record->primary_photo_path),
            'plant_condition' => $record->plant_condition?->value,
            'verification_status' => $record->verification_status?->value,
            'latitude' => (float) $record->latitude,
            'longitude' => (float) $record->longitude,
            'latest_observation_at' => $record->latest_observation_at?->toIso8601String(),
            'created_at' => $record->created_at?->toIso8601String(),
            'author' => [
                'handle' => $record->author?->handle,
                'display_name' => $record->author?->display_name,
                'photo_path' => $record->author?->photo_path,
                'photo_url' => $this->publicStorageUrl($record->author?->photo_path),
            ],
        ];

        if ($record->getAttribute('distance_km') !== null) {
            $payload['distance_km'] = round((float) $record->getAttribute('distance_km'), 3);
        }

        if ($withObservations) {
            $payload['observations'] = $record->observations->map(fn (Observation $observation) => [
                'public_id' => $observation->public_id,
                'photo_path' => $observation->photo_path,
                'photo_url' => $this->publicStorageUrl($observation->photo_path),
                'note' => $observation->note,
                'plant_condition' => $observation->plant_condition?->value,
                'latitude' => (float) $observation->latitude,
                'longitude' => (float) $observation->longitude,
                'source_type' => $observation->source_type?->value,
                'observed_at' => $observation->observed_at?->toIso8601String(),
                'author' => [
                    'handle' => $observation->author?->handle,
                    'display_name' => $observation->author?->display_name,
                    'photo_url' => $this->publicStorageUrl($observation->author?->photo_path),
                ],
            ])->values();
        }

        return $payload;
    }

    private function applyNearbyFilter(Builder $query, float $latitude, float $longitude, float $radiusKm): bool
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            $recordPointSql = 'ST_SetSRID(ST_MakePoint(longitude, latitude), 4326)::geography';
            $searchPointSql = 'ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography';

            $query
                ->select('plant_records.*')
                ->selectRaw("ST_Distance($recordPointSql, $searchPointSql) / 1000 AS distance_km", [$longitude, $latitude])
                ->whereRaw("ST_DWithin($recordPointSql, $searchPointSql, ?)", [
                    $longitude,
                    $latitude,
                    $radiusKm * 1000,
                ]);

            return true;
        }

        $latitudeDelta = $radiusKm / 111.045;
        $longitudeDelta = $radiusKm / max(abs(cos(deg2rad($latitude))) * 111.045, 0.001);

        $query
            ->whereBetween('latitude', [$latitude - $latitudeDelta, $latitude + $latitudeDelta])
            ->whereBetween('longitude', [$longitude - $longitudeDelta, $longitude + $longitudeDelta]);

        return false;
    }

    private function filterNearbyInMemory(
        Collection $records,
        float $latitude,
        float $longitude,
        float $radiusKm,
        int $limit,
    ): Collection {
        return $records
            ->map(function (PlantRecord $record) use ($latitude, $longitude): PlantRecord {
                $record->setAttribute('distance_km', $this->distanceKm(
                    $latitude,
                    $longitude,
                    (float) $record->latitude,
                    (float) $record->longitude,
                ));

                return $record;
            })
            ->filter(fn (PlantRecord $record): bool => (float) $record->getAttribute('distance_km') <= $radiusKm)
            ->sort(function (PlantRecord $left, PlantRecord $right): int {
                return [
                    $left->verification_status === VerificationStatus::VERIFIED ? 0 : 1,
                    (float) $left->getAttribute('distance_km'),
                    -($left->latest_observation_at?->timestamp ?? 0),
                    -($left->created_at?->timestamp ?? 0),
                ] <=> [
                    $right->verification_status === VerificationStatus::VERIFIED ? 0 : 1,
                    (float) $right->getAttribute('distance_km'),
                    -($right->latest_observation_at?->timestamp ?? 0),
                    -($right->created_at?->timestamp ?? 0),
                ];
            })
            ->take($limit)
            ->values();
    }

    private function distanceKm(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float
    {
        $earthRadiusKm = 6371.0088;
        $deltaLatitude = deg2rad($toLatitude - $fromLatitude);
        $deltaLongitude = deg2rad($toLongitude - $fromLongitude);

        $a = sin($deltaLatitude / 2) ** 2
            + cos(deg2rad($fromLatitude))
            * cos(deg2rad($toLatitude))
            * sin($deltaLongitude / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function publicStorageUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return asset("storage/{$path}");
    }
}
