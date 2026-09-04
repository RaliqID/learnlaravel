<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('topic'));
    }

    public function rules(): array
    {
        $topic = $this->route('topic');

        return [
            'name' => ['sometimes', 'string', 'min:3', 'max:100', Rule::unique('topics', 'name')->ignore($topic)],
            'slug' => ['sometimes', 'nullable', 'string', 'max:100', 'alpha_dash', Rule::unique('topics', 'slug')->ignore($topic)],
            'description' => ['nullable', 'string', 'max:5000'],
            'icon' => ['nullable', 'string', 'max:255'],
            'banner' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'is_private' => ['sometimes', 'boolean'],
            'requires_approval' => ['sometimes', 'boolean'],
            'rules' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.min' => 'Topic name must be at least 3 characters',
            'name.unique' => 'A topic with this name already exists',
            'slug.unique' => 'A topic with this slug already exists',
        ];
    }
}
