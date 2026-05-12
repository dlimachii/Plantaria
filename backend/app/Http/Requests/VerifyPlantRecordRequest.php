<?php

namespace App\Http\Requests;

use App\Enums\VerificationStatus;
use App\Http\Requests\Concerns\SanitizesInput;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyPlantRecordRequest extends FormRequest
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
            'verification_status' => [
                'required',
                Rule::in([
                    VerificationStatus::VERIFIED->value,
                    VerificationStatus::REJECTED->value,
                ]),
            ],
            'verified_common_name' => [
                Rule::requiredIf(
                    $this->input('verification_status') === VerificationStatus::VERIFIED->value
                ),
                'nullable',
                'string',
                'max:120',
            ],
            'verified_scientific_name' => [
                Rule::requiredIf(
                    $this->input('verification_status') === VerificationStatus::VERIFIED->value
                ),
                'nullable',
                'string',
                'max:180',
            ],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->sanitizedOnly([
            'verified_common_name' => fn (mixed $value): ?string => $this->sanitizeText($value),
            'verified_scientific_name' => fn (mixed $value): ?string => $this->sanitizeText($value),
            'description' => fn (mixed $value): ?string => $this->sanitizeText($value, preserveNewLines: true),
        ]));
    }
}
