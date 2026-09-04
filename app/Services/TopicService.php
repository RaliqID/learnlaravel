<?php

namespace App\Services;

use App\Models\Topic;
use App\Repositories\TopicRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TopicService
{
    private const LIST_TTL_SECONDS = 300;

    private const REGISTRY_KEY = 'topics:list:keys';

    public function __construct(
        private readonly TopicRepositoryInterface $repository
    ) {
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters, int $page, int $perPage, ?int $userId = null): LengthAwarePaginator
    {
        // No object caching: paginators fail unserialize on the database
        // cache store (__PHP_Incomplete_Class). Query directly.
        return $this->repository->list($filters, $page, $perPage, $userId);
    }

    public function show(Topic $topic, ?int $userId = null): Topic
    {
        return $this->repository->findDetail($topic->id, $userId) ?? $topic;
    }

    public function create(array $data): Topic
    {
        $topic = DB::transaction(fn () => $this->repository->create($data));

        $this->invalidate();

        return $topic;
    }

    public function update(Topic $topic, array $data): Topic
    {
        $updated = DB::transaction(fn () => $this->repository->update($topic, $data));

        $this->invalidate();

        return $updated;
    }

    public function delete(Topic $topic): bool
    {
        $deleted = DB::transaction(fn () => $this->repository->delete($topic));

        $this->invalidate();

        return $deleted;
    }

    public function subscribe(Topic $topic, int $userId): array
    {
        $result = DB::transaction(fn () => $this->repository->subscribe($topic, $userId));

        $this->invalidate();

        return $result;
    }

    public function unsubscribe(Topic $topic, int $userId): array
    {
        $result = DB::transaction(fn () => $this->repository->unsubscribe($topic, $userId));

        $this->invalidate();

        return $result;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function cacheKey(array $filters, int $page, int $perPage, ?int $userId): string
    {
        $sort = $filters['sort'] ?? 'popular';
        $isActive = $filters['is_active'] ?? 'all';
        $scope = $userId ? "user:{$userId}" : 'guest';

        return "topics:list:{$sort}:{$isActive}:{$page}:{$perPage}:{$scope}";
    }

    private function rememberKey(string $key): void
    {
        $keys = Cache::get(self::REGISTRY_KEY, []);
        if (is_array($keys) && !in_array($key, $keys, true)) {
            $keys[] = $key;
            Cache::put(self::REGISTRY_KEY, $keys, now()->addHour());
        }
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
        Cache::forget('feed:categories');
    }
}
