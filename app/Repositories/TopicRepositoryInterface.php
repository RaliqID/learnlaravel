<?php

namespace App\Repositories;

use App\Models\Topic;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface TopicRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function list(array $filters, int $page, int $perPage, ?int $userId = null): LengthAwarePaginator;

    public function findDetail(int $id, ?int $userId = null): ?Topic;

    public function searchActive(string $q, int $page, int $perPage): LengthAwarePaginator;

    public function create(array $data): Topic;

    public function update(Topic $topic, array $data): Topic;

    public function delete(Topic $topic): bool;

    public function isSubscribed(Topic $topic, int $userId): bool;

    /**
     * @return array{topic: Topic, subscribed: bool}
     */
    public function subscribe(Topic $topic, int $userId): array;

    /**
     * @return array{topic: Topic, unsubscribed: bool}
     */
    public function unsubscribe(Topic $topic, int $userId): array;
}
