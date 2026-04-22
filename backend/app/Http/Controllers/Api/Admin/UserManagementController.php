<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateManagedUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserManagementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->ensureAdmin();

        $limit = max(1, min(50, (int) $request->integer('limit', 20)));
        $query = User::query()->orderBy('handle');

        if ($request->filled('q')) {
            $term = $request->string('q')->trim()->value();
            $query->where(function ($builder) use ($term): void {
                $builder
                    ->where('handle', 'like', "%{$term}%")
                    ->orWhere('display_name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->string('role')->value());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->value());
        }

        $users = $query->limit($limit)->get();

        return response()->json([
            'data' => $users->map(fn (User $user) => $this->payload($user, false))->values(),
        ]);
    }

    public function show(string $handle): JsonResponse
    {
        $this->ensureAdmin();

        $user = User::query()
            ->where('handle', Str::lower($handle))
            ->firstOrFail();

        return response()->json([
            'data' => $this->payload($user, true),
        ]);
    }

    public function update(UpdateManagedUserRequest $request, string $handle): JsonResponse
    {
        $this->ensureAdmin();

        $user = User::query()
            ->where('handle', Str::lower($handle))
            ->firstOrFail();

        $validated = $request->validated();

        if (array_key_exists('handle', $validated)) {
            $validated['handle'] = Str::lower($validated['handle']);
        }

        $user->fill($validated)->save();

        return response()->json([
            'data' => $this->payload($user->fresh(), true),
        ]);
    }

    public function ban(string $handle): JsonResponse
    {
        $this->ensureAdmin();

        $user = User::query()
            ->where('handle', Str::lower($handle))
            ->firstOrFail();

        $user->forceFill([
            'status' => UserStatus::BANNED,
        ])->save();

        return response()->json([
            'data' => $this->payload($user, true),
        ]);
    }

    public function destroy(string $handle): JsonResponse
    {
        $this->ensureAdmin();

        $user = User::query()
            ->where('handle', Str::lower($handle))
            ->firstOrFail();

        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado.',
        ]);
    }

    private function payload(User $user, bool $withAdminFields): array
    {
        $payload = [
            'uid' => $user->uid,
            'handle' => $user->handle,
            'display_name' => $user->display_name,
            'photo_path' => $user->photo_path,
            'role' => $user->role->value,
            'status' => $user->status->value,
            'reports_count' => $user->createdRecords()->count(),
            'created_at' => $user->created_at?->toIso8601String(),
        ];

        if ($withAdminFields) {
            $payload += [
                'email' => $user->email,
                'country' => $user->country,
                'province' => $user->province,
                'city' => $user->city,
                'birthdate' => $user->birthdate?->toDateString(),
                'default_lat' => $user->default_lat,
                'default_lng' => $user->default_lng,
                'last_login_at' => $user->last_login_at?->toIso8601String(),
                'last_known_lat' => $user->last_known_lat,
                'last_known_lng' => $user->last_known_lng,
            ];
        }

        return $payload;
    }

    private function ensureAdmin(): void
    {
        /** @var User|null $user */
        $user = auth()->user();

        abort_unless($user && $user->role === UserRole::ADMIN, 403, 'Solo administracion.');
    }
}
