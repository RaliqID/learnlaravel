<?php

namespace App\Repositories;

use App\Models\Post;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface PostRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function getFeed(array $filters, int $page, int $perPage): LengthAwarePaginator;

    /**
     * @return array{headline: ?Post, secondary: Collection}
     */
    public function getHero(): array;

    public function getTrending(string $time, int $limit = 10): Collection;

    public function getEditorsPicks(int $limit = 5): Collection;

    public function getPopularTopics(int $limit = 10): Collection;

    public function getPostsByTopic(int $topicId, int $limit): Collection;

    public function getRecommended(int $limit = 6): Collection;

    public function findById(int $id): ?Post;

    public function findBySlug(string $slug): ?Post;

    public function findDuplicateUrl(string $url, ?int $excludePostId = null): ?Post;

    public function getUserPosts(int $userId, string $status, int $page, int $perPage): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function searchPosts(array $filters, int $page, int $perPage): LengthAwarePaginator;

    public function getUserPublicPosts(int $userId, int $page, int $perPage): LengthAwarePaginator;

    public function create(array $data): Post;

    public function update(Post $post, array $data): Post;

    public function delete(Post $post): bool;

    public function restore(Post $post): bool;

    public function isSlugTaken(string $slug, ?int $excludeId = null): bool;

    public function incrementViewCount(Post $post): void;

    public function relatedPosts(Post $post, int $limit = 6): Collection;

    public function previousPost(Post $post): ?Post;

    public function nextPost(Post $post): ?Post;

    public function getActivityChart(): array;

    public function getCategoryDistribution(int $days): array;
}
