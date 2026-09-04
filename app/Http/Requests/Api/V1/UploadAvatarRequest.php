<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UploadAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'avatar' => [
                'required',
                'image',
                'mimes:jpeg,png,jpg,gif,webp',
                'max:5120',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.required' => 'Avatar image is required',
            'avatar.image' => 'Avatar must be a valid image file',
            'avatar.mimes' => 'Avatar must be a file of type: jpeg, png, jpg, gif, webp',
            'avatar.max' => 'Avatar cannot exceed 5120 kilobytes',
        ];
    }
}
