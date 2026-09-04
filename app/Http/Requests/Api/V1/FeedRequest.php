<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class FeedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sort' => ['sometimes', 'string', 'in:hot,new,top'],
            'time' => ['sometimes', 'string', 'in:hour,day,week,month,year,all'],
            'topic_id' => ['sometimes', 'integer', 'exists:topics,id'],
            'source' => ['sometimes', 'string', 'in:news,community,all'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'sort.in' => 'Sort must be one of: hot, new, top',
            'time.in' => 'Time must be one of: hour, day, week, month, year, all',
            'topic_id.exists' => 'The selected topic does not exist',
            'per_page.max' => 'Per page cannot exceed 100 items',
        ];
    }

    public function sort(): string
    {
        return $this->input('sort', 'new');
    }

    public function timeRange(): string
    {
        return $this->input('time', 'all');
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
            'time' => $this->timeRange(),
            'topic_id' => $this->input('topic_id'),
            'source' => $this->input('source'),
        ];
    }
}
