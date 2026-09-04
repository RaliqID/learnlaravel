<?php

namespace App\Services;

use App\Repositories\PostRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class FeedService
{
    private const REGISTRY_KEY = 'feed:keys';

    public function __construct(
        private readonly PostRepositoryInterface $repository
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getFeed(array $filters, int $page, int $perPage): LengthAwarePaginator
    {
        // No object caching: Eloquent models/paginators do not survive
        // serialize/unserialize reliably on the database cache store
        // (__PHP_Incomplete_Class). Queries are indexed; hit DB directly.
        return $this->repository->getFeed($filters, $page, $perPage);
    }

    /**
     * @return array{headline: ?\App\Models\Post, secondary: Collection}
     */
    public function getHero(): array
    {
        return $this->repository->getHero();
    }

    public function getTrending(string $time = 'day'): Collection
    {
        return $this->repository->getTrending($time);
    }

    public function getRecommended(): Collection
    {
        return $this->repository->getRecommended();
    }

    public function getCategories(int $limit = 10): Collection
    {
        return $this->repository->getPopularTopics($limit);
    }

    public function getEditorsPicks(): Collection
    {
        return $this->repository->getEditorsPicks();
    }

    public function getSections(): array
    {
        $topics = $this->repository->getPopularTopics(6);
        
        $sections = [];
        foreach ($topics as $topic) {
            $posts = $this->repository->getPostsByTopic($topic->id, 4);
            
            $sections[] = [
                'topic' => [
                    'id' => $topic->id,
                    'name' => $topic->name,
                    'slug' => $topic->slug,
                ],
                'posts' => $posts,
            ];
        }
        
        return $sections;
    }

    public function getActivityChart(): array
    {
        return $this->repository->getActivityChart();
    }

    public function getCategoryDistribution(int $days): array
    {
        return $this->repository->getCategoryDistribution($days);
    }

    public function invalidate(): void
    {
        $keys = Cache::get(self::REGISTRY_KEY, []);

        if (is_array($keys)) {
            foreach ($keys as $key) {
                Cache::forget($key);
            }
        }

        Cache::forget(self::REGISTRY_KEY);
    }

    private function rememberKey(string $key): void
    {
        $keys = Cache::get(self::REGISTRY_KEY, []);

        if (is_array($keys) && !in_array($key, $keys, true)) {
            $keys[] = $key;
            Cache::put(self::REGISTRY_KEY, $keys, now()->addHour());
        }
    }
}
