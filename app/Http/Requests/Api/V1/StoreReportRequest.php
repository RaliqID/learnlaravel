<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && !$this->user()->is_banned;
    }

    public function rules(): array
    {
        return [
            'reportable_type' => ['required', 'string', Rule::in(['post', 'comment'])],
            'reportable_id' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', Rule::in(['spam', 'harassment', 'hateful', 'violence', 'nsfw', 'misinformation', 'other'])],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'reportable_type.in' => 'You can only report posts or comments',
            'reason.in' => 'Invalid report reason',
            'description.max' => 'Description cannot exceed 1000 characters',
        ];
    }
}
