<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\PostRepositoryInterface;
use App\Repositories\TopicRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchService
{
    public function __construct(
        private readonly PostRepositoryInterface $postRepository,
        private readonly TopicRepositoryInterface $topicRepository,
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function searchPosts(array $filters, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->postRepository->searchPosts($filters, $page, $perPage);
    }

    public function searchTopics(string $q, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->topicRepository->searchActive($q, $page, $perPage);
    }

    public function searchUsers(string $q, int $page, int $perPage): LengthAwarePaginator
    {
        $term = mb_strtolower(trim($q));

        return User::query()
            ->where('is_banned', false)
            ->where(fn (Builder $query) => $query
                ->whereRaw('LOWER(username) LIKE ?', ["%{$term}%"])
                ->orWhereRaw('LOWER(display_name) LIKE ?', ["%{$term}%"]))
            ->orderByDesc('post_count')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}
