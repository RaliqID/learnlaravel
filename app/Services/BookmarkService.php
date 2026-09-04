<?php

namespace App\Services;

use App\Models\Bookmark;
use App\Models\Post;
use App\Models\User;
use App\Repositories\BookmarkRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class BookmarkService
{
    public function __construct(
        private readonly BookmarkRepositoryInterface $repository,
        private readonly FeedService $feedService,
    ) {
    }

    public function bookmark(User $user, Post $post, ?string $collection = null, ?string $notes = null): Bookmark
    {
        $this->guardBookmarkable($post);

        return DB::transaction(function () use ($user, $post, $collection, $notes) {
            $existing = $this->repository->findByUserAndPost($user->id, $post->id);

            if ($existing) {
                return $this->repository->update($existing, [
                    'collection_name' => $collection,
                    'notes' => $notes,
                ]);
            }

            $bookmark = $this->repository->create([
                'user_id' => $user->id,
                'post_id' => $post->id,
                'collection_name' => $collection,
                'notes' => $notes,
            ]);

            $this->repository->incrementPostBookmarkCount($post->id);

            return $bookmark->fresh(['post']);
        });
    }

    public function unbookmark(User $user, Post $post): bool
    {
        return DB::transaction(function () use ($user, $post) {
            $bookmark = $this->repository->findByUserAndPost($user->id, $post->id);

            if (!$bookmark) {
                return false;
            }

            $this->repository->delete($bookmark);
            $this->repository->decrementPostBookmarkCount($post->id);

            $this->invalidateCache($post);

            return true;
        });
    }

    public function removeById(User $user, Bookmark $bookmark): bool
    {
        if ($bookmark->user_id !== $user->id) {
            return false;
        }

        return DB::transaction(function () use ($bookmark) {
            $postId = $bookmark->post_id;

            $this->repository->delete($bookmark);
            $this->repository->decrementPostBookmarkCount($postId);

            $post = Post::find($postId);
            if ($post) {
                $this->invalidateCache($post);
            }

            return true;
        });
    }

    public function listForUser(User $user, ?string $collection, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->repository->listForUser($user->id, $collection, $page, $perPage);
    }

    public function collections(User $user): Collection
    {
        return $this->repository->collectionsForUser($user->id);
    }

    public function isBookmarked(User $user, Post $post): bool
    {
        return $this->repository->isBookmarked($user->id, $post->id);
    }

    private function guardBookmarkable(Post $post): void
    {
        if ($post->status !== 'published') {
            throw new UnprocessableEntityHttpException('This post is not available for bookmarking.');
        }
    }

    private function invalidateCache(Post $post): void
    {
        $this->feedService->invalidate();
        Cache::forget("post:related:{$post->id}");
        Cache::forget("post:navigation:{$post->id}");
    }
}
