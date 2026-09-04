<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnalyticsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'period' => ['sometimes', Rule::in(['today', '7d', '30d', '90d', 'all'])],
            'granularity' => ['sometimes', Rule::in(['day', 'month'])],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ];
    }

    public function period(): string
    {
        return $this->input('period', '30d');
    }

    public function granularity(): string
    {
        return $this->input('granularity', 'day');
    }

    public function limit(): int
    {
        return (int) $this->input('limit', 10);
    }
}
