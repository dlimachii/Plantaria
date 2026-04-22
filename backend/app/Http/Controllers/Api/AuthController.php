<?php

namespace App\Http\Controllers\Api;

use App\Enums\EventType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\AppEvent;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'handle' => $request->string('handle')->lower()->value(),
            'display_name' => $request->string('display_name')->value(),
            'email' => $request->string('email')->lower()->value(),
            'password' => $request->string('password')->value(),
            'country' => $request->string('country')->value(),
            'province' => $request->input('province'),
            'city' => $request->input('city'),
            'birthdate' => $request->input('birthdate'),
            'default_lat' => $request->input('default_lat'),
            'default_lng' => $request->input('default_lng'),
            'role' => UserRole::USER,
            'status' => UserStatus::ACTIVE,
        ]);

        $token = $user->createToken($request->input('device_name', 'android-client'))->plainTextToken;

        AppEvent::record(EventType::USER_REGISTERED, $user);

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()
            ->where('handle', strtolower($request->string('handle')->value()))
            ->first();

        if (! $user || ! Hash::check($request->string('password')->value(), $user->password)) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 422);
        }

        if ($user->status === UserStatus::BANNED) {
            return response()->json([
                'message' => 'La cuenta está bloqueada.',
            ], 403);
        }

        $user->forceFill([
            'last_login_at' => now(),
            'last_known_lat' => $request->input('last_known_lat'),
            'last_known_lng' => $request->input('last_known_lng'),
        ])->save();

        $token = $user->createToken($request->input('device_name', 'android-client'))->plainTextToken;

        AppEvent::record(EventType::USER_LOGGED_IN, $user);

        return response()->json([
            'token' => $token,
            'user' => $this->userPayload($user->fresh()),
        ]);
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'user' => $this->userPayload(auth()->user()),
        ]);
    }

    public function logout(): JsonResponse
    {
        auth()->user()?->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Sesion cerrada.',
        ]);
    }

    private function userPayload(?User $user): array
    {
        return [
            'uid' => $user?->uid,
            'handle' => $user?->handle,
            'display_name' => $user?->display_name,
            'email' => $user?->email,
            'photo_path' => $user?->photo_path,
            'country' => $user?->country,
            'province' => $user?->province,
            'city' => $user?->city,
            'default_lat' => $user?->default_lat,
            'default_lng' => $user?->default_lng,
            'birthdate' => $user?->birthdate?->toDateString(),
            'role' => $user?->role?->value,
            'status' => $user?->status?->value,
            'last_login_at' => $user?->last_login_at?->toIso8601String(),
        ];
    }
}
