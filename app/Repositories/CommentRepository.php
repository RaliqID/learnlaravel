<?php

namespace App\Repositories;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

class CommentRepository implements CommentRepositoryInterface
{
    /**
     * Get paginated top-level comments (no parent) for a post.
     */
    public function getPostComments(int $postId, string $sort, int $page, int $perPage): LengthAwarePaginator
    {
        $query = Comment::query()
            ->with('user:id,username,avatar,display_name')
            ->where('post_id', $postId)
            ->whereNull('parent_id')
            ->where('is_deleted', false);

        if ($sort === 'top') {
            $query->orderByDesc('vote_score');
        } elseif ($sort === 'old') {
            $query->orderBy('created_at');
        } else {
            $query->orderByDesc('created_at');
        }

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function getCommentWithReplies(int $commentId): ?Comment
    {
        return Comment::with(['user:id,username,avatar,display_name', 'replies.user'])
            ->where('id', $commentId)
            ->first();
    }

    public function getUserComments(int $userId, int $page, int $perPage): LengthAwarePaginator
    {
        return Comment::query()
            ->with(['user:id,username,avatar,display_name', 'post:id,title,slug,status,is_locked,is_approved'])
            ->where('user_id', $userId)
            ->where('is_deleted', false)
            ->whereHas('post', function ($query) {
                $query->published()->approved()->visible();
            })
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function create(array $data): Comment
    {
        return Comment::create([
            'user_id' => $data['user_id'],
            'post_id' => $data['post_id'],
            'parent_id' => $data['parent_id'] ?? null,
            'content' => $data['content'],
            'vote_score' => 0,
            'upvote_count' => 0,
            'downvote_count' => 0,
            'reply_count' => 0,
            'depth' => $data['depth'] ?? 0,
            'is_deleted' => false,
        ]);
    }

    public function update(Comment $comment, array $data): Comment
    {
        $data['edited_at'] = now();
        $comment->update($data);

        return $comment->fresh(['user:id,username,avatar,display_name']);
    }

    public function delete(Comment $comment): bool
    {
        return (bool) $comment->delete();
    }

    public function restore(Comment $comment): bool
    {
        return (bool) $comment->restore();
    }

    public function find(int $id): ?Comment
    {
        return Comment::find($id);
    }

    public function getUserCommentVote(int $userId, int $commentId): ?int
    {
        return \App\Models\CommentVote::where('user_id', $userId)
            ->where('comment_id', $commentId)
            ->value('vote_type');
    }

    public function incrementCommentCount(int $postId): void
    {
        \App\Models\Post::where('id', $postId)->increment('comment_count');
    }

    public function decrementCommentCount(int $postId): void
    {
        \App\Models\Post::where('id', $postId)
            ->where('comment_count', '>', 0)
            ->decrement('comment_count');
    }

    public function incrementRepliesCount(int $commentId): void
    {
        if ($commentId > 0) {
            Comment::where('id', $commentId)->increment('reply_count');
        }
    }

    public function decrementRepliesCount(int $commentId): void
    {
        if ($commentId > 0) {
            Comment::where('id', $commentId)
                ->where('reply_count', '>', 0)
                ->decrement('reply_count');
        }
    }

    private function applySort($query, string $sort)
    {
        if ($sort === 'top') {
            return $query->orderByDesc('vote_score');
        }
        if ($sort === 'new') {
            return $query->orderByDesc('created_at');
        }
        if ($sort === 'old') {
            return $query->orderBy('created_at');
        }
        return $query;
    }
}