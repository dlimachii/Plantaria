<?php

namespace App\Models;

use App\Enums\FlagStatus;
use App\Enums\FlagTargetType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class ModerationFlag extends Model
{
    protected $fillable = [
        'uid',
        'target_type',
        'target_id',
        'created_by_user_id',
        'reason',
        'status',
        'resolved_by_user_id',
        'resolved_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $flag): void {
            $flag->uid ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'status' => FlagStatus::class,
            'target_type' => FlagTargetType::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by_user_id');
    }

    public function record(): HasOne
    {
        return $this->hasOne(PlantRecord::class, 'id', 'target_id');
    }

    public function observation(): HasOne
    {
        return $this->hasOne(Observation::class, 'id', 'target_id');
    }

    public function userTarget(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'target_id');
    }
}
