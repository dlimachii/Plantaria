<?php

namespace App\Http\Requests;

use App\Enums\PlantCondition;
use App\Enums\UserRole;
use App\Enums\VerificationStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateManagedPlantRecordRequest extends FormRequest
{
    protected $errorBag = 'recordUpdate';

    public function authorize(): bool
    {
        return $this->user()?->role === UserRole::ADMIN;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'provisional_common_name' => ['required', 'string', 'max:120'],
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
            'primary_photo_path' => ['required', 'string', 'max:255'],
            'plant_condition' => ['required', Rule::enum(PlantCondition::class)],
            'verification_status' => ['required', Rule::enum(VerificationStatus::class)],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }
}
