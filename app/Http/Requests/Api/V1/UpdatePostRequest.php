<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('post'));
    }

    public function rules(): array
    {
        $post = $this->route('post');

        return [
            'title' => ['sometimes', 'string', 'min:10', 'max:300'],
            'content' => ['sometimes', 'nullable', 'string', 'max:50000'],
            'url' => ['sometimes', 'nullable', 'url:http,https', 'max:2048'],
            'topic_id' => ['sometimes', 'integer', 'exists:topics,id'],
            'status' => ['sometimes', 'string', Rule::in(['draft', 'published', 'archived'])],
            'is_nsfw' => ['sometimes', 'boolean'],
            'meta_title' => ['sometimes', 'nullable', 'string', 'max:60'],
            'meta_description' => ['sometimes', 'nullable', 'string', 'max:160'],
            'image' => [
                'sometimes',
                'nullable',
                'image',
                'max:10240',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.min' => 'Post title must be at least 10 characters',
            'title.max' => 'Post title cannot exceed 300 characters',
            'url.url' => 'Please enter a valid URL starting with http:// or https://',
            'topic_id.exists' => 'The selected topic does not exist',
            'status.in' => 'Status must be draft, published, or archived',
            'image.image' => 'The file must be an image (jpeg, png, bmp, gif, svg, or webp)',
            'image.max' => 'Image cannot exceed 10MB',
        ];
    }
}
