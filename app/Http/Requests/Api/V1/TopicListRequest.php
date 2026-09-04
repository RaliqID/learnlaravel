<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class TopicListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sort' => ['sometimes', 'string', 'in:popular,name,newest'],
            'is_active' => ['sometimes', 'string', 'in:true,false'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'sort.in' => 'Sort must be one of: popular, name, newest',
            'is_active.in' => 'is_active must be true or false',
            'per_page.max' => 'Per page cannot exceed 100 items',
        ];
    }

    public function sort(): string
    {
        return $this->input('sort', 'popular');
    }

    public function page(): int
    {
        return (int) $this->input('page', 1);
    }

    public function perPage(): int
    {
        return (int) $this->input('per_page', 20);
    }

    public function filters(): array
    {
        return [
            'sort' => $this->sort(),
            'is_active' => $this->input('is_active'),
        ];
    }
}
