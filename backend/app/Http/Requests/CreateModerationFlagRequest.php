<?php

namespace App\Http\Requests;

use App\Enums\FlagTargetType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateModerationFlagRequest extends FormRequest
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
            'target_type' => ['required', Rule::enum(FlagTargetType::class)],
            'target_reference' => ['required', 'string', 'max:64'],
            'reason' => ['required', 'string', 'min:5', 'max:2000'],
        ];
    }
}
