<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:1', 'max:100'],
            'sort' => ['sometimes', 'string', 'in:relevance,newest,oldest,popular'],
            'topic_id' => ['sometimes', 'integer', 'exists:topics,id'],
            'time' => ['sometimes', 'string', 'in:hour,day,week,month,year,all'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'q.required' => 'A search query is required',
            'q.min' => 'Search query must be at least 1 character',
            'q.max' => 'Search query cannot exceed 100 characters',
            'sort.in' => 'Sort must be one of: relevance, newest, oldest, popular',
            'time.in' => 'Time must be one of: hour, day, week, month, year, all',
            'topic_id.exists' => 'The selected topic does not exist',
            'per_page.max' => 'Per page cannot exceed 100 items',
        ];
    }

    public function searchTerm(): string
    {
        return trim($this->input('q'));
    }

    public function sort(): string
    {
        return $this->input('sort', 'relevance');
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

    /**
     * @return array<string, mixed>
     */
    public function postFilters(): array
    {
        return [
            'q' => $this->searchTerm(),
            'sort' => $this->sort(),
            'time' => $this->timeRange(),
            'topic_id' => $this->input('topic_id'),
        ];
    }
}
