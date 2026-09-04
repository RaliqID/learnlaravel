<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\CommentRepositoryInterface;
use App\Repositories\PostRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class UserProfileService
{
    public function __construct(
        private readonly PostRepositoryInterface $postRepository,
        private readonly CommentRepositoryInterface $commentRepository,
    ) {
    }

    public function publicProfile(User $user, ?int $viewerId = null): User
    {
        return $user->loadCount([
            'posts' => fn ($query) => $query->published()->approved()->visible(),
            'comments' => fn ($query) => $query->where('is_deleted', false),
            'followers',
            'following',
            'postVotes as likes_count' => fn ($query) => $query->where('vote_type', 1),
        ])->loadExists([
            'followers as is_following' => fn ($query) => $query->where('follows.follower_id', $viewerId ?? -1),
        ]);
    }

    public function updateProfile(User $user, array $data): User
    {
        $user->update($data);

        return $user->fresh();
    }

    public function userPosts(User $user, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->postRepository->getUserPublicPosts($user->id, $page, $perPage);
    }

    public function userComments(User $user, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->commentRepository->getUserComments($user->id, $page, $perPage);
    }
}
