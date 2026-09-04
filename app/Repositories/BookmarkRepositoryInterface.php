<?php

namespace App\Repositories;

use App\Models\Bookmark;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface BookmarkRepositoryInterface
{
    public function listForUser(int $userId, ?string $collection, int $page, int $perPage): LengthAwarePaginator;

    public function findByUserAndPost(int $userId, int $postId): ?Bookmark;

    public function find(int $id): ?Bookmark;

    public function create(array $data): Bookmark;

    public function update(Bookmark $bookmark, array $data): Bookmark;

    public function delete(Bookmark $bookmark): bool;

    public function incrementPostBookmarkCount(int $postId): void;

    public function decrementPostBookmarkCount(int $postId): void;

    public function isBookmarked(int $userId, int $postId): bool;

    public function collectionsForUser(int $userId): Collection;
}
