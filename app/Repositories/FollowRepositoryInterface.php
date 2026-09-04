<?php

namespace App\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;

interface FollowRepositoryInterface
{
    public function isFollowing(int $followerId, int $followingId): bool;

    /**
     * Create a follow relationship. Idempotent: returns false when the pair
     * already exists (unique constraint also prevents duplicates at DB level).
     */
    public function follow(int $followerId, int $followingId): bool;

    /**
     * Remove a follow relationship. Idempotent: returns false when absent.
     */
    public function unfollow(int $followerId, int $followingId): bool;

    public function followers(int $userId, ?int $viewerId, int $page, int $perPage): LengthAwarePaginator;

    public function following(int $userId, ?int $viewerId, int $page, int $perPage): LengthAwarePaginator;
}
