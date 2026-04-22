<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
        $userId = $this->user()?->id;

        return [
            'handle' => [
                'sometimes',
                'string',
                'min:3',
                'max:16',
                'regex:/^[a-z0-9_.]+$/i',
                Rule::unique('users', 'handle')->ignore($userId),
            ],
            'display_name' => ['sometimes', 'string', 'max:120'],
            'photo_path' => ['sometimes', 'nullable', 'string', 'max:255'],
            'country' => ['sometimes', 'string', 'max:100'],
            'province' => ['sometimes', 'nullable', 'string', 'max:100'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'birthdate' => ['sometimes', 'nullable', 'date', 'before:today'],
            'default_lat' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'default_lng' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
