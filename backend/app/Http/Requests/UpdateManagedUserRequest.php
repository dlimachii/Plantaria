<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateManagedUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $routeHandle = $this->route('handle');

        return [
            'handle' => [
                'sometimes',
                'string',
                'min:3',
                'max:16',
                'regex:/^[a-z0-9_.]+$/i',
                Rule::unique('users', 'handle', 'handle')->ignore($routeHandle, 'handle'),
            ],
            'display_name' => ['sometimes', 'string', 'max:120'],
            'photo_path' => ['sometimes', 'nullable', 'string', 'max:255'],
            'country' => ['sometimes', 'string', 'max:100'],
            'province' => ['sometimes', 'nullable', 'string', 'max:100'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'birthdate' => ['sometimes', 'nullable', 'date', 'before:today'],
            'default_lat' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'default_lng' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'role' => ['sometimes', Rule::enum(UserRole::class)],
            'status' => ['sometimes', Rule::enum(UserStatus::class)],
        ];
    }
}
