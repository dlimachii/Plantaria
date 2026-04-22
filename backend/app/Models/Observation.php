<?php

namespace App\Models;

use App\Enums\ObservationSourceType;
use App\Enums\PlantCondition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Observation extends Model
{
    protected $fillable = [
        'uid',
        'public_id',
        'plant_record_id',
        'author_user_id',
        'photo_path',
        'note',
        'plant_condition',
        'latitude',
        'longitude',
        'source_type',
        'observed_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $observation): void {
            $observation->uid ??= (string) Str::uuid();
            $observation->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return [
            'observed_at' => 'datetime',
            'plant_condition' => PlantCondition::class,
            'source_type' => ObservationSourceType::class,
        ];
    }

    public function plantRecord(): BelongsTo
    {
        return $this->belongsTo(PlantRecord::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }
}
