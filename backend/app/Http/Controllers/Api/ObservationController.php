<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventType;
use App\Enums\ObservationSourceType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreObservationRequest;
use App\Models\AppEvent;
use App\Models\Observation;
use App\Models\PlantRecord;
use Illuminate\Http\JsonResponse;

class ObservationController extends Controller
{
    public function store(StoreObservationRequest $request, string $publicId): JsonResponse
    {
        $record = PlantRecord::query()
            ->where('public_id', $publicId)
            ->firstOrFail();

        $observation = Observation::create([
            'plant_record_id' => $record->id,
            'author_user_id' => $request->user()->id,
            'photo_path' => $request->string('photo_path')->value(),
            'note' => $request->input('note'),
            'plant_condition' => $request->input('plant_condition'),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'source_type' => ObservationSourceType::UPDATE,
            'observed_at' => $request->date('observed_at') ?? now(),
        ]);

        $record->forceFill([
            'latest_observation_at' => $observation->observed_at,
        ])->save();

        AppEvent::record(EventType::OBSERVATION_CREATED, $request->user(), $record);

        return response()->json([
            'data' => [
                'public_id' => $observation->public_id,
                'record_public_id' => $record->public_id,
                'photo_path' => $observation->photo_path,
                'photo_url' => asset("storage/{$observation->photo_path}"),
                'note' => $observation->note,
                'plant_condition' => $observation->plant_condition?->value,
                'latitude' => (float) $observation->latitude,
                'longitude' => (float) $observation->longitude,
                'source_type' => $observation->source_type?->value,
                'observed_at' => $observation->observed_at?->toIso8601String(),
            ],
        ], 201);
    }
}
