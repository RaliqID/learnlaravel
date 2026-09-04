<?php

namespace App\Repositories;

use App\Models\Topic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class TopicRepository implements TopicRepositoryInterface
{
    public function list(array $filters, int $page, int $perPage, ?int $userId = null): LengthAwarePaginator
    {
        $query = Topic::query();

        if ($userId) {
            $query->withExists([
                'subscribers as is_subscribed' => fn (Builder $q) => $q->where('users.id', $userId),
            ]);
        }

        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOL));
        } else {
            $query->where('is_active', true);
        }

        $this->applySort($query, $filters['sort'] ?? 'popular');

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function findDetail(int $id, ?int $userId = null): ?Topic
    {
        $query = Topic::query();

        if ($userId) {
            $query->withExists([
                'subscribers as is_subscribed' => fn (Builder $q) => $q->where('users.id', $userId),
            ]);
        }

        return $query->find($id);
    }

    public function searchActive(string $q, int $page, int $perPage): LengthAwarePaginator
    {
        $term = mb_strtolower(trim($q));

        return Topic::query()
            ->where('is_active', true)
            ->where(fn (Builder $query) => $query
                ->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"])
                ->orWhereRaw('LOWER(description) LIKE ?', ["%{$term}%"]))
            ->orderByDesc('post_count')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function create(array $data): Topic
    {
        return Topic::create($data);
    }

    public function update(Topic $topic, array $data): Topic
    {
        $topic->update($data);

        return $topic->fresh();
    }

    public function delete(Topic $topic): bool
    {
        return (bool) $topic->delete();
    }

    public function isSubscribed(Topic $topic, int $userId): bool
    {
        return $topic->subscribers()->whereKey($userId)->exists();
    }

    public function subscribe(Topic $topic, int $userId): array
    {
        $exists = $topic->subscribers()->whereKey($userId)->exists();

        if ($exists) {
            return ['topic' => $topic, 'subscribed' => false];
        }

        $topic->subscribers()->attach($userId, ['created_at' => now()]);
        $topic->increment('subscriber_count');

        return ['topic' => $topic->fresh(), 'subscribed' => true];
    }

    public function unsubscribe(Topic $topic, int $userId): array
    {
        $exists = $topic->subscribers()->whereKey($userId)->exists();

        if (!$exists) {
            return ['topic' => $topic, 'unsubscribed' => false];
        }

        $topic->subscribers()->detach($userId);

        Topic::query()
            ->where('id', $topic->id)
            ->where('subscriber_count', '>', 0)
            ->decrement('subscriber_count');

        return ['topic' => $topic->fresh(), 'unsubscribed' => true];
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'name' => $query->orderBy('name'),
            'newest' => $query->orderByDesc('created_at'),
            default => $query->orderByDesc('subscriber_count'),
        };
    }
}
