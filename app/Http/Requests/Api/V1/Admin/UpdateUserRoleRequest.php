<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->id !== $this->route('user')?->id;
    }

    public function rules(): array
    {
        return [
            'role' => ['required', 'string', Rule::in(['user', 'moderator', 'admin'])],
        ];
    }
}
