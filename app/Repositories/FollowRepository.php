<?php

namespace App\Repositories;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class FollowRepository implements FollowRepositoryInterface
{
    private const USER_COLUMNS = [
        'users.id',
        'users.username',
        'users.display_name',
        'users.avatar',
        'users.bio',
        'users.karma_score',
        'users.created_at',
    ];

    public function isFollowing(int $followerId, int $followingId): bool
    {
        return Follow::query()
            ->where('follower_id', $followerId)
            ->where('following_id', $followingId)
            ->exists();
    }

    public function follow(int $followerId, int $followingId): bool
    {
        return (bool) Follow::query()->insertOrIgnore([
            'follower_id' => $followerId,
            'following_id' => $followingId,
            'created_at' => now(),
        ]);
    }

    public function unfollow(int $followerId, int $followingId): bool
    {
        $deleted = Follow::query()
            ->where('follower_id', $followerId)
            ->where('following_id', $followingId)
            ->delete();

        return $deleted > 0;
    }

    public function followers(int $userId, ?int $viewerId, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->listQuery('followers', $userId)
            ->when($viewerId, fn (Builder $q) => $this->withViewerFollows($q, $viewerId))
            ->paginate($perPage, self::USER_COLUMNS, 'page', $page);
    }

    public function following(int $userId, ?int $viewerId, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->listQuery('following', $userId)
            ->when($viewerId, fn (Builder $q) => $this->withViewerFollows($q, $viewerId))
            ->paginate($perPage, self::USER_COLUMNS, 'page', $page);
    }

    private function listQuery(string $relation, int $userId): Builder
    {
        return User::query()
            ->join('follows as f', function ($join) use ($relation, $userId) {
                if ($relation === 'followers') {
                    $join->on('users.id', '=', 'f.follower_id')->where('f.following_id', $userId);
                } else {
                    $join->on('users.id', '=', 'f.following_id')->where('f.follower_id', $userId);
                }
            })
            ->orderByDesc('f.created_at');
    }

    private function withViewerFollows(Builder $query, int $viewerId): Builder
    {
        return $query->withExists([
            'followers as is_following' => fn (Builder $q) => $q->where('follows.follower_id', $viewerId),
        ]);
    }
}
