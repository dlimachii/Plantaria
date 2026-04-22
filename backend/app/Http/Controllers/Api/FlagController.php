<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventType;
use App\Enums\FlagStatus;
use App\Enums\FlagTargetType;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateModerationFlagRequest;
use App\Models\AppEvent;
use App\Models\ModerationFlag;
use App\Models\Observation;
use App\Models\PlantRecord;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class FlagController extends Controller
{
    public function store(CreateModerationFlagRequest $request): JsonResponse
    {
        $user = $request->user();
        $targetType = FlagTargetType::from($request->string('target_type')->value());
        $reference = $request->string('target_reference')->trim()->value();

        $targetId = match ($targetType) {
            FlagTargetType::RECORD => PlantRecord::query()
                ->where('public_id', $reference)
                ->valueOrFail('id'),
            FlagTargetType::OBSERVATION => Observation::query()
                ->where('public_id', $reference)
                ->valueOrFail('id'),
            FlagTargetType::USER => User::query()
                ->where('handle', Str::lower($reference))
                ->valueOrFail('id'),
        };

        $flag = ModerationFlag::create([
            'target_type' => $targetType,
            'target_id' => $targetId,
            'created_by_user_id' => $user->id,
            'reason' => $request->string('reason')->value(),
            'status' => FlagStatus::OPEN,
        ]);

        AppEvent::record(EventType::FLAG_CREATED, $user, metadata: [
            'target_type' => $targetType->value,
            'target_reference' => $reference,
        ]);

        return response()->json([
            'data' => [
                'uid' => $flag->uid,
                'target_type' => $flag->target_type->value,
                'reason' => $flag->reason,
                'status' => $flag->status->value,
                'created_at' => $flag->created_at?->toIso8601String(),
            ],
        ], 201);
    }
}
