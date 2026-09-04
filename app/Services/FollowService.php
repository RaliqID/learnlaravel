<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\FollowRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class FollowService
{
    public function __construct(
        private readonly FollowRepositoryInterface $repository,
        private readonly NotificationService $notificationService,
    ) {
    }

    /**
     * Follow a user. Idempotent.
     *
     * @return array{following: bool, created: bool}
     */
    public function follow(User $actor, User $target): array
    {
        $this->guardSelfFollow($actor, $target);

        $created = $this->repository->follow($actor->id, $target->id);

        if ($created) {
            $this->notificationService->notifyUserFollowed($target, $actor->id);
        }

        return [
            'following' => true,
            'created' => $created,
        ];
    }

    /**
     * Unfollow a user. Idempotent.
     *
     * @return array{following: bool, removed: bool}
     */
    public function unfollow(User $actor, User $target): array
    {
        $this->guardSelfFollow($actor, $target);

        $removed = $this->repository->unfollow($actor->id, $target->id);

        return [
            'following' => false,
            'removed' => $removed,
        ];
    }

    public function isFollowing(User $actor, User $target): bool
    {
        return $this->repository->isFollowing($actor->id, $target->id);
    }

    public function followers(User $user, ?User $viewer, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->repository->followers($user->id, $viewer?->id, $page, $perPage);
    }

    public function following(User $user, ?User $viewer, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->repository->following($user->id, $viewer?->id, $page, $perPage);
    }

    private function guardSelfFollow(User $actor, User $target): void
    {
        if ($actor->id === $target->id) {
            throw new UnprocessableEntityHttpException('You cannot follow yourself.');
        }
    }
}
