<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResolveReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && in_array($this->user()->role, ['admin', 'moderator'], true);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['resolved', 'dismissed'])],
            'resolution_note' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Status must be one of: resolved, dismissed',
            'resolution_note.max' => 'Resolution note cannot exceed 1000 characters',
        ];
    }
}
