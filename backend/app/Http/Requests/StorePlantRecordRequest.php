<?php

namespace App\Http\Requests;

use App\Enums\PlantCondition;
use App\Http\Requests\Concerns\SanitizesInput;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePlantRecordRequest extends FormRequest
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
            'provisional_common_name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'primary_photo_path' => ['required', 'string', 'max:255'],
            'plant_condition' => ['nullable', Rule::enum(PlantCondition::class)],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->sanitizedOnly([
            'provisional_common_name' => fn (mixed $value): ?string => $this->sanitizeText($value),
            'description' => fn (mixed $value): ?string => $this->sanitizeText($value, preserveNewLines: true),
            'primary_photo_path' => fn (mixed $value): ?string => $this->sanitizeText($value),
        ]));
    }
}
