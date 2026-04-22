<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\EventType;
use App\Enums\FlagStatus;
use App\Enums\FlagTargetType;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\VerifyPlantRecordRequest;
use App\Models\AppEvent;
use App\Models\ModerationFlag;
use App\Models\PlantRecord;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModerationController extends Controller
{
    public function pending(Request $request): JsonResponse
    {
        $this->ensureModerator();

        $limit = max(1, min(50, (int) $request->integer('limit', 20)));
        $sort = $request->string('sort')->value() ?: 'created_at';
        $direction = $request->string('direction')->lower()->value() === 'asc' ? 'asc' : 'desc';

        $query = PlantRecord::query()
            ->with('author')
            ->where('verification_status', VerificationStatus::PENDING->value);

        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->date('created_from'));
        }

        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->date('created_to'));
        }

        if (in_array($sort, ['created_at', 'latest_observation_at', 'public_id'], true)) {
            $query->orderBy($sort, $direction);
        }

        $records = $query->limit($limit)->get();

        return response()->json([
            'data' => $records->map(fn (PlantRecord $record) => [
                'public_id' => $record->public_id,
                'provisional_common_name' => $record->provisional_common_name,
                'primary_photo_path' => $record->primary_photo_path,
                'verification_status' => $record->verification_status->value,
                'created_at' => $record->created_at?->toIso8601String(),
                'latitude' => (float) $record->latitude,
                'longitude' => (float) $record->longitude,
                'author' => [
                    'handle' => $record->author?->handle,
                    'display_name' => $record->author?->display_name,
                ],
            ])->values(),
        ]);
    }

    public function verify(VerifyPlantRecordRequest $request, string $publicId): JsonResponse
    {
        $moderator = $this->ensureModerator();

        $record = PlantRecord::query()
            ->where('public_id', $publicId)
            ->firstOrFail();

        $status = VerificationStatus::from($request->string('verification_status')->value());

        $record->forceFill([
            'verification_status' => $status,
            'verified_common_name' => $status === VerificationStatus::VERIFIED
                ? $request->string('verified_common_name')->value()
                : null,
            'verified_scientific_name' => $status === VerificationStatus::VERIFIED
                ? $request->string('verified_scientific_name')->value()
                : null,
            'verified_by_user_id' => $moderator->id,
            'verified_at' => now(),
            'description' => $request->input('description', $record->description),
        ])->save();

        AppEvent::record(EventType::RECORD_VERIFIED, $moderator, $record, metadata: [
            'verification_status' => $status->value,
        ]);

        return response()->json([
            'data' => [
                'public_id' => $record->public_id,
                'verification_status' => $record->verification_status->value,
                'verified_common_name' => $record->verified_common_name,
                'verified_scientific_name' => $record->verified_scientific_name,
                'verified_at' => $record->verified_at?->toIso8601String(),
            ],
        ]);
    }

    public function flags(Request $request): JsonResponse
    {
        $this->ensureModerator();

        $limit = max(1, min(50, (int) $request->integer('limit', 20)));
        $query = ModerationFlag::query()
            ->with(['reporter', 'resolver'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->value());
        }

        if ($request->filled('target_type')) {
            $query->where('target_type', $request->string('target_type')->value());
        }

        $flags = $query->limit($limit)->get();

        return response()->json([
            'data' => $flags->map(fn (ModerationFlag $flag) => [
                'uid' => $flag->uid,
                'target_type' => $flag->target_type->value,
                'target_id' => $flag->target_id,
                'reason' => $flag->reason,
                'status' => $flag->status->value,
                'created_at' => $flag->created_at?->toIso8601String(),
                'reporter' => [
                    'handle' => $flag->reporter?->handle,
                    'display_name' => $flag->reporter?->display_name,
                ],
                'resolver' => [
                    'handle' => $flag->resolver?->handle,
                    'display_name' => $flag->resolver?->display_name,
                ],
            ])->values(),
        ]);
    }

    public function resolveFlag(Request $request, string $uid): JsonResponse
    {
        $moderator = $this->ensureModerator();

        $request->validate([
            'status' => ['required', 'in:reviewing,resolved,rejected'],
        ]);

        $flag = ModerationFlag::query()
            ->where('uid', $uid)
            ->firstOrFail();

        $flag->forceFill([
            'status' => FlagStatus::from($request->string('status')->value()),
            'resolved_by_user_id' => $moderator->id,
            'resolved_at' => now(),
        ])->save();

        return response()->json([
            'data' => [
                'uid' => $flag->uid,
                'status' => $flag->status->value,
                'resolved_at' => $flag->resolved_at?->toIso8601String(),
            ],
        ]);
    }

    private function ensureModerator(): User
    {
        /** @var User|null $user */
        $user = auth()->user();

        abort_unless(
            $user && in_array($user->role, [UserRole::MOD, UserRole::ADMIN], true),
            403,
            'Solo moderacion.'
        );

        return $user;
    }
}
