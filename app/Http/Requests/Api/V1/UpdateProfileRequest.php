<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'username' => [
                'sometimes',
                'string',
                'min:3',
                'max:50',
                'regex:/^[a-zA-Z0-9_-]+$/',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'display_name' => ['sometimes', 'string', 'min:1', 'max:100'],
            'bio' => ['sometimes', 'nullable', 'string', 'max:500'],
            'avatar' => ['sometimes', 'nullable', 'string', 'url', 'max:255'],
            'website' => ['sometimes', 'nullable', 'string', 'url', 'max:255'],
            'location' => ['sometimes', 'nullable', 'string', 'max:100'],
            'show_email' => ['sometimes', 'boolean'],
            'email_notifications' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.min' => 'Username must be at least 3 characters',
            'username.max' => 'Username cannot exceed 50 characters',
            'username.regex' => 'Username can only contain letters, numbers, underscores and dashes',
            'username.unique' => 'This username is already taken',
            'display_name.max' => 'Display name cannot exceed 100 characters',
            'bio.max' => 'Bio cannot exceed 500 characters',
            'avatar.url' => 'Avatar must be a valid URL',
            'website.url' => 'Website must be a valid URL',
            'location.max' => 'Location cannot exceed 100 characters',
        ];
    }
}
