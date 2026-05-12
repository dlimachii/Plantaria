<?php

namespace App\Http\Controllers\Web;

use App\Enums\EventType;
use App\Enums\FlagTargetType;
use App\Enums\PlantCondition;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateManagedPlantRecordRequest;
use App\Models\AppEvent;
use App\Models\ModerationFlag;
use App\Models\PlantRecord;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModerationPanelController extends Controller
{
    public function pending(Request $request): View
    {
        $this->ensureModerator($request);

        $search = trim($request->string('q')->value());
        $selectedStatus = $request->string('status')->value() ?: VerificationStatus::PENDING->value;

        $query = PlantRecord::query()
            ->with('author');

        if ($selectedStatus !== 'all') {
            $status = VerificationStatus::tryFrom($selectedStatus) ?? VerificationStatus::PENDING;
            $selectedStatus = $status->value;
            $query->where('verification_status', $status->value);
        }

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('public_id', 'like', "%{$search}%")
                    ->orWhere('provisional_common_name', 'like', "%{$search}%")
                    ->orWhere('verified_common_name', 'like', "%{$search}%")
                    ->orWhere('verified_scientific_name', 'like', "%{$search}%");
            });
        }

        $records = $query
            ->orderByDesc('latest_observation_at')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.moderation.pending', [
            'records' => $records,
            'search' => $search,
            'selectedStatus' => $selectedStatus,
            'statuses' => VerificationStatus::cases(),
        ]);
    }

    public function show(Request $request, string $publicId): View
    {
        $this->ensureModerator($request);

        $record = PlantRecord::query()
            ->with(['author', 'observations.author', 'verifier'])
            ->where('public_id', $publicId)
            ->firstOrFail();

        $observationIds = $record->observations->pluck('id');
        $relatedFlags = ModerationFlag::query()
            ->with(['reporter', 'resolver', 'observation'])
            ->where(function ($query) use ($record, $observationIds): void {
                $query->where(function ($recordQuery) use ($record): void {
                    $recordQuery
                        ->where('target_type', FlagTargetType::RECORD->value)
                        ->where('target_id', $record->id);
                });

                if ($observationIds->isNotEmpty()) {
                    $query->orWhere(function ($observationQuery) use ($observationIds): void {
                        $observationQuery
                            ->where('target_type', FlagTargetType::OBSERVATION->value)
                            ->whereIn('target_id', $observationIds);
                    });
                }
            })
            ->orderByDesc('created_at')
            ->get();

        $moderationEvents = AppEvent::query()
            ->with('user')
            ->where('plant_record_id', $record->id)
            ->whereIn('event_type', [
                EventType::RECORD_CREATED->value,
                EventType::OBSERVATION_CREATED->value,
                EventType::RECORD_UPDATED->value,
                EventType::RECORD_VERIFIED->value,
            ])
            ->latest('occurred_at')
            ->limit(18)
            ->get();

        return view('admin.moderation.show', [
            'record' => $record,
            'relatedFlags' => $relatedFlags,
            'moderationEvents' => $moderationEvents,
            'conditions' => PlantCondition::cases(),
            'statuses' => VerificationStatus::cases(),
        ]);
    }

    public function update(UpdateManagedPlantRecordRequest $request, string $publicId): RedirectResponse
    {
        $admin = $this->ensureAdmin($request);

        $record = PlantRecord::query()
            ->where('public_id', $publicId)
            ->firstOrFail();

        $status = VerificationStatus::from($request->string('verification_status')->value());
        $wasStatus = $record->verification_status ?? VerificationStatus::PENDING;

        $record->forceFill([
            'provisional_common_name' => $request->string('provisional_common_name')->trim()->value(),
            'verified_common_name' => $status === VerificationStatus::VERIFIED
                ? $request->string('verified_common_name')->trim()->value()
                : null,
            'verified_scientific_name' => $status === VerificationStatus::VERIFIED
                ? $request->string('verified_scientific_name')->trim()->value()
                : null,
            'description' => $request->input('description'),
            'primary_photo_path' => $request->string('primary_photo_path')->value(),
            'plant_condition' => PlantCondition::from($request->string('plant_condition')->value()),
            'verification_status' => $status,
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'verified_by_user_id' => $this->resolvedVerifierId($record, $admin, $status, $wasStatus),
            'verified_at' => $this->resolvedVerifiedAt($record, $status, $wasStatus),
        ])->save();

        AppEvent::record(EventType::RECORD_UPDATED, $admin, $record, metadata: [
            'source' => 'web_admin',
            'previous_verification_status' => $wasStatus->value,
            'verification_status' => $status->value,
        ]);

        $this->recordEventForAuthor($record, $admin, EventType::RECORD_UPDATED, metadata: [
            'source' => 'web_admin',
            'previous_verification_status' => $wasStatus->value,
            'verification_status' => $status->value,
        ]);

        return redirect()
            ->route('admin.moderation.show', $record->public_id)
            ->with('status', 'Registro actualizado.');
    }

    public function verify(Request $request, string $publicId): RedirectResponse
    {
        $moderator = $this->ensureModerator($request);

        $validated = $request->validate([
            'verified_common_name' => ['required', 'string', 'max:120'],
            'verified_scientific_name' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $record = PlantRecord::query()
            ->where('public_id', $publicId)
            ->firstOrFail();

        $record->forceFill([
            'verification_status' => VerificationStatus::VERIFIED,
            'verified_common_name' => $validated['verified_common_name'],
            'verified_scientific_name' => $validated['verified_scientific_name'],
            'description' => $validated['description'] ?? $record->description,
            'verified_by_user_id' => $moderator->id,
            'verified_at' => now(),
        ])->save();

        AppEvent::record(EventType::RECORD_VERIFIED, $moderator, $record, metadata: [
            'verification_status' => VerificationStatus::VERIFIED->value,
            'source' => 'web_admin',
        ]);

        $this->recordEventForAuthor($record, $moderator, EventType::RECORD_VERIFIED, metadata: [
            'verification_status' => VerificationStatus::VERIFIED->value,
            'reviewed_by' => $moderator->handle,
            'source' => 'web_admin',
        ]);

        return redirect()
            ->route('admin.moderation.show', $record->public_id)
            ->with('status', 'Registro verificado.');
    }

    public function reject(Request $request, string $publicId): RedirectResponse
    {
        $moderator = $this->ensureModerator($request);

        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $record = PlantRecord::query()
            ->where('public_id', $publicId)
            ->firstOrFail();

        $record->forceFill([
            'verification_status' => VerificationStatus::REJECTED,
            'verified_common_name' => null,
            'verified_scientific_name' => null,
            'description' => $validated['description'] ?? $record->description,
            'verified_by_user_id' => $moderator->id,
            'verified_at' => now(),
        ])->save();

        AppEvent::record(EventType::RECORD_VERIFIED, $moderator, $record, metadata: [
            'verification_status' => VerificationStatus::REJECTED->value,
            'source' => 'web_admin',
        ]);

        $this->recordEventForAuthor($record, $moderator, EventType::RECORD_VERIFIED, metadata: [
            'verification_status' => VerificationStatus::REJECTED->value,
            'reviewed_by' => $moderator->handle,
            'source' => 'web_admin',
        ]);

        return redirect()
            ->route('admin.moderation.show', $record->public_id)
            ->with('status', 'Registro rechazado.');
    }

    private function ensureModerator(Request $request)
    {
        $user = $request->user();

        abort_unless(
            $user && in_array($user->role, [UserRole::MOD, UserRole::ADMIN], true),
            403
        );

        return $user;
    }

    private function ensureAdmin(Request $request): User
    {
        $user = $request->user();

        abort_unless($user && $user->role === UserRole::ADMIN, 403);

        return $user;
    }

    private function resolvedVerifierId(
        PlantRecord $record,
        User $admin,
        VerificationStatus $status,
        VerificationStatus $wasStatus,
    ): ?int {
        if ($status === VerificationStatus::PENDING) {
            return null;
        }

        if ($wasStatus === $status && $record->verified_by_user_id !== null) {
            return $record->verified_by_user_id;
        }

        return $admin->id;
    }

    private function resolvedVerifiedAt(
        PlantRecord $record,
        VerificationStatus $status,
        VerificationStatus $wasStatus,
    ) {
        if ($status === VerificationStatus::PENDING) {
            return null;
        }

        if ($wasStatus === $status && $record->verified_at !== null) {
            return $record->verified_at;
        }

        return now();
    }

    private function recordEventForAuthor(
        PlantRecord $record,
        User $actor,
        EventType $type,
        array $metadata = [],
    ): void {
        $authorId = $record->created_by_user_id;
        if ($authorId === null || $authorId === $actor->id) {
            return;
        }

        $author = User::query()->find($authorId);
        if (! $author) {
            return;
        }

        AppEvent::record($type, $author, $record, metadata: [
            ...$metadata,
            'actor_handle' => $actor->handle,
            'actor_role' => $actor->role?->value,
        ]);
    }
}
