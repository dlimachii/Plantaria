<?php

namespace App\Http\Requests;

use App\Enums\VerificationStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VerifyPlantRecordRequest extends FormRequest
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
}
