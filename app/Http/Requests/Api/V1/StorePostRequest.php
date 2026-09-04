<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Post::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:10', 'max:300'],
            'content' => ['required_if:post_type,text', 'nullable', 'string', 'max:50000'],
            'url' => [
                'required_if:post_type,link',
                'nullable',
                'url:http,https',
                'max:2048',
            ],
            'post_type' => ['required', 'string', Rule::in(['link', 'text', 'image'])],
            'topic_id' => ['nullable', 'integer', 'exists:topics,id'],
            'status' => ['sometimes', 'string', Rule::in(['draft', 'published'])],
            'is_nsfw' => ['sometimes', 'boolean'],
            'meta_title' => ['sometimes', 'nullable', 'string', 'max:60'],
            'meta_description' => ['sometimes', 'nullable', 'string', 'max:160'],
            'image' => [
                'required_if:post_type,image',
                'nullable',
                'image',
                'max:10240',
                Rule::when($this->input('post_type') !== 'image', ['prohibited']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Post title is required',
            'title.min' => 'Post title must be at least 10 characters',
            'title.max' => 'Post title cannot exceed 300 characters',
            'content.required_if' => 'Content is required for text posts',
            'url.required_if' => 'URL is required for link posts',
            'url.url' => 'Please enter a valid URL starting with http:// or https://',
            'post_type.in' => 'Post type must be link, text, or image',
            'topic_id.required' => 'Please select a topic',
            'topic_id.exists' => 'The selected topic does not exist',
            'status.in' => 'Status must be draft or published',
            'image.required_if' => 'Image is required for image posts',
            'image.image' => 'The file must be an image (jpeg, png, bmp, gif, svg, or webp)',
            'image.max' => 'Image cannot exceed 10MB',
        ];
    }
}
