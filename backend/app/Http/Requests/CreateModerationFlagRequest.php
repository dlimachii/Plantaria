<?php

namespace App\Http\Requests;

use App\Enums\FlagTargetType;
use App\Http\Requests\Concerns\SanitizesInput;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateModerationFlagRequest extends FormRequest
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
            'target_type' => ['required', Rule::enum(FlagTargetType::class)],
            'target_reference' => ['required', 'string', 'max:64'],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->sanitizedOnly([
            'target_reference' => fn (mixed $value): ?string => $this->sanitizeText($value),
            'reason' => fn (mixed $value): ?string => $this->sanitizeText($value, preserveNewLines: true),
        ]));
    }
}
