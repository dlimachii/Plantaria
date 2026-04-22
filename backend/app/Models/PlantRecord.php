<?php

namespace App\Models;

use App\Enums\PlantCondition;
use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PlantRecord extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uid',
        'public_id',
        'created_by_user_id',
        'provisional_common_name',
        'verified_common_name',
        'verified_scientific_name',
        'description',
        'primary_photo_path',
        'plant_condition',
        'verification_status',
        'verified_by_user_id',
        'verified_at',
        'latitude',
        'longitude',
        'latest_observation_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $record): void {
            $record->uid ??= (string) Str::uuid();
            $record->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'latest_observation_at' => 'datetime',
            'plant_condition' => PlantCondition::class,
            'verification_status' => VerificationStatus::class,
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class)->orderByDesc('observed_at');
    }

    public function events(): HasMany
    {
        return $this->hasMany(AppEvent::class);
    }
}
