<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class BanUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && in_array($this->user()->role, ['admin', 'moderator'], true);
    }

    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:1', 'max:500'],
            'duration_days' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:3650'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'A ban reason is required',
            'reason.max' => 'Ban reason cannot exceed 500 characters',
            'duration_days.max' => 'Ban duration cannot exceed 3650 days',
        ];
    }
}
