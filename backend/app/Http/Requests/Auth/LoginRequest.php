<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
        return [
            'handle' => ['required', 'string', 'max:16'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
            'last_known_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'last_known_lng' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }
}
