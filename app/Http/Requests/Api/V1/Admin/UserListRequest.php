<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'role' => ['sometimes', 'string', Rule::in(['user', 'moderator', 'admin'])],
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'banned' => ['sometimes', 'in:0,1,true,false'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function role(): ?string
    {
        return $this->input('role');
    }

    public function search(): ?string
    {
        return $this->input('search');
    }

    public function banned(): bool
    {
        return in_array($this->input('banned'), ['1', 'true', 1, true], true);
    }

    public function page(): int
    {
        return (int) $this->input('page', 1);
    }

    public function perPage(): int
    {
        return (int) $this->input('per_page', 20);
    }
}
