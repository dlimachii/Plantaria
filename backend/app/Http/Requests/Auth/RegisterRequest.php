<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\SanitizesInput;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    use SanitizesInput;

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
            'handle' => ['required', 'string', 'min:3', 'max:16', 'regex:/^[a-z0-9_.]+$/i', 'unique:users,handle'],
            'display_name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers(),
            ],
            'country' => ['required', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'birthdate' => ['nullable', 'date', 'before:today'],
            'default_lat' => ['nullable', 'numeric', 'between:-90,90'],
            'default_lng' => ['nullable', 'numeric', 'between:-180,180'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->sanitizedOnly([
            'handle' => fn (mixed $value): ?string => $this->sanitizeHandle($value),
            'display_name' => fn (mixed $value): ?string => $this->sanitizeText($value),
            'email' => fn (mixed $value): ?string => $this->sanitizeEmail($value),
            'country' => fn (mixed $value): ?string => $this->sanitizeText($value),
            'province' => fn (mixed $value): ?string => $this->sanitizeText($value),
            'city' => fn (mixed $value): ?string => $this->sanitizeText($value),
            'device_name' => fn (mixed $value): ?string => $this->sanitizeText($value),
        ]));
    }
}
