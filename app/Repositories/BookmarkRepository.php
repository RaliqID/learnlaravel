<?php

namespace App\Repositories;

use App\Models\Bookmark;
use App\Models\Post;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class BookmarkRepository implements BookmarkRepositoryInterface
{
    private const LIST_WITH = [
        'post:id,user_id,topic_id,title,slug,post_type,image_url,thumbnail_url,vote_score,comment_count,bookmark_count,published_at,created_at',
        'post.topic:id,name,slug',
    ];

    public function listForUser(int $userId, ?string $collection, int $page, int $perPage): LengthAwarePaginator
    {
        $query = Bookmark::query()
            ->with(self::LIST_WITH)
            ->where('user_id', $userId)
            ->when($collection !== null && $collection !== '', fn ($q) => $q->where('collection_name', $collection))
            ->orderByDesc('created_at');

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function findByUserAndPost(int $userId, int $postId): ?Bookmark
    {
        return Bookmark::where('user_id', $userId)
            ->where('post_id', $postId)
            ->first();
    }

    public function find(int $id): ?Bookmark
    {
        return Bookmark::find($id);
    }

    public function create(array $data): Bookmark
    {
        return Bookmark::create([
            'user_id' => $data['user_id'],
            'post_id' => $data['post_id'],
            'collection_name' => $data['collection_name'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    public function update(Bookmark $bookmark, array $data): Bookmark
    {
        $bookmark->update([
            'collection_name' => $data['collection_name'] ?? $bookmark->collection_name,
            'notes' => $data['notes'] ?? $bookmark->notes,
        ]);

        return $bookmark->fresh(['post']);
    }

    public function delete(Bookmark $bookmark): bool
    {
        return (bool) $bookmark->delete();
    }

    public function incrementPostBookmarkCount(int $postId): void
    {
        Post::where('id', $postId)->increment('bookmark_count');
    }

    public function decrementPostBookmarkCount(int $postId): void
    {
        Post::where('id', $postId)
            ->where('bookmark_count', '>', 0)
            ->decrement('bookmark_count');
    }

    public function isBookmarked(int $userId, int $postId): bool
    {
        return Bookmark::where('user_id', $userId)
            ->where('post_id', $postId)
            ->exists();
    }

    public function collectionsForUser(int $userId): Collection
    {
        return Bookmark::query()
            ->where('user_id', $userId)
            ->whereNotNull('collection_name')
            ->selectRaw('collection_name, COUNT(*) as total')
            ->groupBy('collection_name')
            ->orderBy('collection_name')
            ->get();
    }
}
