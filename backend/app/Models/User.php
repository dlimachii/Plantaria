<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'uid',
        'handle',
        'display_name',
        'email',
        'password',
        'photo_path',
        'country',
        'province',
        'city',
        'default_lat',
        'default_lng',
        'birthdate',
        'role',
        'status',
        'last_login_at',
        'last_known_lat',
        'last_known_lng',
    ];

    protected $hidden = [
        'id',
        'password',
        'remember_token',
        'deleted_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            $user->uid ??= (string) Str::uuid();
            $user->handle = Str::lower($user->handle);
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'birthdate' => 'date',
            'last_login_at' => 'datetime',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    public function createdRecords(): HasMany
    {
        return $this->hasMany(PlantRecord::class, 'created_by_user_id');
    }

    public function observations(): HasMany
    {
        return $this->hasMany(Observation::class, 'author_user_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(AppEvent::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isModerator(): bool
    {
        return in_array($this->role, [UserRole::MOD, UserRole::ADMIN], true);
    }
}
