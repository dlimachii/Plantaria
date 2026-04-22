<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventType;
use App\Enums\ObservationSourceType;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePlantRecordRequest;
use App\Models\AppEvent;
use App\Models\Observation;
use App\Models\PlantRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PlantRecordController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PlantRecord::query()
            ->with(['author', 'observations'])
            ->whereNull('deleted_at');

        if ($request->filled('status')) {
            $query->where('verification_status', $request->string('status')->value());
        }

        if ($request->filled('q')) {
            $term = $request->string('q')->trim()->value();

            $query->where(function ($builder) use ($term): void {
                $builder
                    ->where('public_id', $term)
                    ->orWhere('provisional_common_name', 'like', "%{$term}%")
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

        $records = $query
            ->orderByRaw("CASE WHEN verification_status = ? THEN 0 ELSE 1 END", [VerificationStatus::VERIFIED->value])
            ->orderByDesc('latest_observation_at')
            ->orderByDesc('created_at')
            ->limit((int) $request->integer('limit', 20))
            ->get();

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
            $record = PlantRecord::create([
                'created_by_user_id' => $user->id,
                'provisional_common_name' => $request->string('provisional_common_name')->value(),
                'description' => $request->input('description'),
                'primary_photo_path' => $request->string('primary_photo_path')->value(),
                'plant_condition' => $request->input('plant_condition'),
                'latitude' => $request->input('latitude'),
                'longitude' => $request->input('longitude'),
                'latest_observation_at' => now(),
            ]);

            Observation::create([
                'plant_record_id' => $record->id,
                'author_user_id' => $user->id,
                'photo_path' => $request->string('primary_photo_path')->value(),
                'note' => $request->input('description'),
                'plant_condition' => $request->input('plant_condition'),
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

    private function publicStorageUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        return asset("storage/{$path}");
    }
}
