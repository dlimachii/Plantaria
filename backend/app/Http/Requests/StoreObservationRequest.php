<?php

namespace App\Http\Requests;

use App\Enums\PlantCondition;
use App\Http\Requests\Concerns\SanitizesInput;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreObservationRequest extends FormRequest
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
            'photo_path' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
            'plant_condition' => ['nullable', Rule::enum(PlantCondition::class)],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'observed_at' => ['nullable', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->sanitizedOnly([
            'photo_path' => fn (mixed $value): ?string => $this->sanitizeText($value),
            'note' => fn (mixed $value): ?string => $this->sanitizeText($value, preserveNewLines: true),
        ]));
    }
}
