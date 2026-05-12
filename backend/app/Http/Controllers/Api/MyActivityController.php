<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventType;
use App\Enums\ObservationSourceType;
use App\Http\Controllers\Controller;
use App\Models\AppEvent;
use App\Models\ModerationFlag;
use App\Models\Observation;
use App\Models\PlantRecord;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class MyActivityController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $limit = max(1, min(50, (int) $request->integer('limit', 20)));

        $activity = collect()
            ->merge($this->recordActivity($user, $limit))
            ->merge($this->observationActivity($user, $limit))
            ->merge($this->flagActivity($user, $limit))
            ->merge($this->eventActivity($user, $limit))
            ->sortByDesc('occurred_at')
            ->take($limit)
            ->values();

        return response()->json([
            'data' => $activity,
        ]);
    }

    private function recordActivity(User $user, int $limit): Collection
    {
        return PlantRecord::query()
            ->where('created_by_user_id', $user->id)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (PlantRecord $record): array => [
                'id' => 'record:' . $record->uid,
                'type' => EventType::RECORD_CREATED->value,
                'label' => 'Reporte creado',
                'description' => $record->verified_common_name ?? $record->provisional_common_name,
                'occurred_at' => $record->created_at?->toIso8601String(),
                'record_public_id' => $record->public_id,
                'record_name' => $record->verified_common_name ?? $record->provisional_common_name,
                'photo_url' => $this->publicStorageUrl($record->primary_photo_path),
                'status' => $record->verification_status?->value,
                'metadata' => [
                    'latitude' => (float) $record->latitude,
                    'longitude' => (float) $record->longitude,
                ],
            ]);
    }

    private function observationActivity(User $user, int $limit): Collection
    {
        return Observation::query()
            ->with('plantRecord')
            ->where('author_user_id', $user->id)
            ->where('source_type', ObservationSourceType::UPDATE->value)
            ->latest('observed_at')
            ->limit($limit)
            ->get()
            ->map(function (Observation $observation): array {
                $record = $observation->plantRecord;

                return [
                    'id' => 'observation:' . $observation->uid,
                    'type' => EventType::OBSERVATION_CREATED->value,
                    'label' => 'Observación añadida',
                    'description' => $record
                        ? ($record->verified_common_name ?? $record->provisional_common_name)
                        : 'Registro no disponible',
                    'occurred_at' => $observation->observed_at?->toIso8601String(),
                    'record_public_id' => $record?->public_id,
                    'record_name' => $record
                        ? ($record->verified_common_name ?? $record->provisional_common_name)
                        : null,
                    'photo_url' => $this->publicStorageUrl($observation->photo_path),
                    'status' => $observation->plant_condition?->value,
                    'metadata' => [
                        'observation_public_id' => $observation->public_id,
                        'note' => $observation->note,
                    ],
                ];
            });
    }

    private function flagActivity(User $user, int $limit): Collection
    {
        return ModerationFlag::query()
            ->where('created_by_user_id', $user->id)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (ModerationFlag $flag): array => [
                'id' => 'flag:' . $flag->uid,
                'type' => EventType::FLAG_CREATED->value,
                'label' => 'Denuncia enviada',
                'description' => $flag->reason,
                'occurred_at' => $flag->created_at?->toIso8601String(),
                'record_public_id' => null,
                'record_name' => null,
                'photo_url' => null,
                'status' => $flag->status?->value,
                'metadata' => [
                    'target_type' => $flag->target_type?->value,
                    'target_id' => $flag->target_id,
                ],
            ]);
    }

    private function eventActivity(User $user, int $limit): Collection
    {
        $eventTypes = [
            EventType::RECORD_UPDATED,
            EventType::RECORD_VERIFIED,
            EventType::FLAG_UPDATED,
            EventType::PROFILE_UPDATED,
            EventType::USER_UPDATED,
            EventType::USER_BANNED,
            EventType::USER_DELETED,
        ];

        return AppEvent::query()
            ->with('plantRecord')
            ->where('user_id', $user->id)
            ->whereIn('event_type', array_map(fn (EventType $type): string => $type->value, $eventTypes))
            ->latest('occurred_at')
            ->limit($limit)
            ->get()
            ->map(fn (AppEvent $event): array => $this->eventPayload($event));
    }

    private function eventPayload(AppEvent $event): array
    {
        $record = $event->plantRecord;
        $metadata = $event->metadata ?? [];

        return [
            'id' => 'event:' . $event->uid,
            'type' => $event->event_type->value,
            'label' => $this->eventLabel($event),
            'description' => $this->eventDescription($event),
            'occurred_at' => $event->occurred_at?->toIso8601String(),
            'record_public_id' => $record?->public_id,
            'record_name' => $record ? ($record->verified_common_name ?? $record->provisional_common_name) : null,
            'photo_url' => $this->publicStorageUrl($record?->primary_photo_path),
            'status' => $metadata['verification_status'] ?? $metadata['status'] ?? null,
            'metadata' => $metadata,
        ];
    }

    private function eventLabel(AppEvent $event): string
    {
        return match ($event->event_type) {
            EventType::RECORD_UPDATED => 'Registro editado',
            EventType::RECORD_VERIFIED => match ($event->metadata['verification_status'] ?? null) {
                'rejected' => 'Registro rechazado',
                default => 'Registro verificado',
            },
            EventType::FLAG_UPDATED => 'Flag actualizado',
            EventType::PROFILE_UPDATED => 'Perfil actualizado',
            EventType::USER_UPDATED => 'Usuario actualizado',
            EventType::USER_BANNED => 'Usuario bloqueado',
            EventType::USER_DELETED => 'Usuario eliminado',
            default => 'Actividad',
        };
    }

    private function eventDescription(AppEvent $event): string
    {
        $metadata = $event->metadata ?? [];
        $record = $event->plantRecord;

        return match ($event->event_type) {
            EventType::PROFILE_UPDATED => 'Cambios guardados en tu perfil.',
            EventType::FLAG_UPDATED => 'Estado: ' . ($metadata['status'] ?? 'actualizado'),
            EventType::USER_UPDATED, EventType::USER_BANNED, EventType::USER_DELETED => '@' . ($metadata['target_handle'] ?? 'usuario'),
            default => $record
                ? ($record->verified_common_name ?? $record->provisional_common_name)
                : ($metadata['target_reference'] ?? 'Sin detalle'),
        };
    }

    private function publicStorageUrl(?string $path): ?string
    {
        if ($path === null || trim($path) === '') {
            return null;
        }

        return asset('storage/' . ltrim($path, '/'));
    }
}
