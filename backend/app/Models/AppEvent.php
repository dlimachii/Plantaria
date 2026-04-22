<?php

namespace App\Models;

use App\Enums\EventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AppEvent extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'uid',
        'event_type',
        'user_id',
        'role_snapshot',
        'plant_record_id',
        'search_query',
        'search_type',
        'metadata',
        'occurred_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $event): void {
            $event->uid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'event_type' => EventType::class,
            'metadata' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public static function record(
        EventType $type,
        ?User $user = null,
        ?PlantRecord $record = null,
        ?string $searchQuery = null,
        ?string $searchType = null,
        ?array $metadata = null,
    ): self {
        return self::create([
            'event_type' => $type->value,
            'user_id' => $user?->id,
            'role_snapshot' => $user?->role?->value,
            'plant_record_id' => $record?->id,
            'search_query' => $searchQuery,
            'search_type' => $searchType,
            'metadata' => $metadata,
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plantRecord(): BelongsTo
    {
        return $this->belongsTo(PlantRecord::class);
    }
}
