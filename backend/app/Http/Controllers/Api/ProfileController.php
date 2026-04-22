<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\AppEvent;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    public function show(string $handle): JsonResponse
    {
        $user = User::query()
            ->where('handle', Str::lower($handle))
            ->firstOrFail();

        AppEvent::record(EventType::PROFILE_VIEWED, auth()->user(), metadata: [
            'profile_handle' => $user->handle,
        ]);

        return response()->json([
            'user' => $this->publicPayload($user),
        ]);
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->fill($request->validated());

        if ($request->filled('handle')) {
            $user->handle = Str::lower($request->string('handle')->value());
        }

        $user->save();

        return response()->json([
            'user' => $this->publicPayload($user->fresh()),
        ]);
    }

    private function publicPayload(User $user): array
    {
        return [
            'uid' => $user->uid,
            'handle' => $user->handle,
            'display_name' => $user->display_name,
            'photo_path' => $user->photo_path,
            'country' => $user->country,
            'province' => $user->province,
            'city' => $user->city,
            'role' => $user->role->value,
            'reports_count' => $user->createdRecords()->count(),
        ];
    }
}
