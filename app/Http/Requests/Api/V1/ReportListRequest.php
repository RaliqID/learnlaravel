<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReportListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && in_array($this->user()->role, ['admin', 'moderator'], true);
    }

    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', Rule::in(['pending', 'reviewing', 'resolved', 'dismissed'])],
            'type' => ['sometimes', 'string', Rule::in(['post', 'comment'])],
            'sort' => ['sometimes', 'string', Rule::in(['newest', 'oldest'])],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function page(): int
    {
        return (int) $this->input('page', 1);
    }

    public function perPage(): int
    {
        return (int) $this->input('per_page', 20);
    }

    /**
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return [
            'status' => $this->input('status'),
            'type' => $this->input('type'),
            'sort' => $this->input('sort', 'newest'),
        ];
    }
}
