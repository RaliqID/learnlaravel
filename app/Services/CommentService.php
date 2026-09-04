<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Repositories\CommentRepositoryInterface;
use App\Repositories\VoteRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Auth\Access\AuthorizationException;

class CommentService
{
    public function __construct(
        private readonly CommentRepositoryInterface $commentRepository,
        private readonly VoteRepositoryInterface $voteRepository,
        private readonly FeedService $feedService,
        private readonly NotificationService $notificationService,
    ) {
    }

    public function createComment(Post $post, array $data): Comment
    {
        $this->guardPostCommentable($post);

        return DB::transaction(function () use ($post, $data) {
            $parentId = $data['parent_id'] ?? null;

            $comment = $this->commentRepository->create([
                'user_id' => $data['user_id'],
                'post_id' => $post->id,
                'parent_id' => $parentId,
                'content' => $data['content'],
                'depth' => $data['depth'] ?? 0,
            ]);

            if ($post->status === 'published') {
                $this->commentRepository->incrementCommentCount($post->id);
            }

            if ($parentId !== null) {
                $this->commentRepository->incrementRepliesCount($parentId);
            }

            $this->notificationService->notifyCommentReply($comment, (int) $data['user_id']);
            $this->notificationService->notifyCommentPosted($comment, (int) $data['user_id']);

            $this->invalidateCache($post);

            return $comment->fresh(['user']);
        });
    }

    public function updateComment(Comment $comment, array $data, User $actor): Comment
    {
        if ($comment->user_id !== $actor->id) {
            throw new AuthorizationException('You are not authorized to edit this comment.');
        }

        if ($comment->is_deleted) {
            throw new UnprocessableEntityHttpException('This comment has been deleted.');
        }

        $updated = $this->commentRepository->update($comment, $data);

        $this->invalidateCache($comment->post);

        return $updated;
    }

    public function deleteComment(Comment $comment, User $actor): bool
    {
        $canDelete = $comment->user_id === $actor->id
            || $actor->role === 'admin'
            || $actor->role === 'moderator';

        if (!$canDelete) {
            throw new AuthorizationException('You are not authorized to delete this comment.');
        }

        if ($comment->is_deleted) {
            throw new NotFoundHttpException('Comment not found');
        }

        $this->commentRepository->delete($comment);

        if ($comment->parent_id !== null) {
            $this->commentRepository->decrementRepliesCount($comment->parent_id);
        }

        $this->commentRepository->decrementCommentCount($comment->post_id);

        $this->invalidateCache($comment->post);

        return true;
    }

    public function restoreComment(Comment $comment, User $actor): Comment
    {
        if ($actor->role !== 'admin' && $actor->role !== 'moderator') {
            throw new AuthorizationException('You are not authorized to restore this comment.');
        }

        if ($comment->trashed()) {
            $this->commentRepository->restore($comment);
            $comment = $comment->fresh(['user']);

            if ($comment->post->status === 'published') {
                $this->commentRepository->incrementCommentCount($comment->post_id);
            }

            if ($comment->parent_id !== null) {
                $this->commentRepository->incrementRepliesCount($comment->parent_id);
            }
        }

        $this->invalidateCache($comment->post);

        return $comment;
    }

    public function voteComment(Comment $comment, User $user, int $voteType): Comment
    {
        return DB::transaction(function () use ($comment, $user, $voteType) {
            $this->guardCommentVotable($comment);

            $current = $this->voteRepository->getUserCommentVote($user->id, $comment->id);

            if ($current === $voteType) {
                return $comment->fresh(['user']);
            }

            if ($current === null) {
                $this->voteRepository->insertCommentVote($user->id, $comment->id, $voteType);
            } else {
                $this->voteRepository->updateCommentVote($user->id, $comment->id, $current, $voteType);
            }

            $this->recalculateCommentVoteScore($comment);
            $this->notificationService->notifyCommentVoted($comment, $user->id);
            $this->invalidateCache($comment->post);

            return $comment->fresh(['user']);
        });
    }

    public function removeVoteFromComment(Comment $comment, User $user): void
    {
        DB::transaction(function () use ($comment, $user) {
            $this->guardCommentVotable($comment);

            $this->voteRepository->removeCommentVote($user->id, $comment->id);

            $this->recalculateCommentVoteScore($comment);
            $this->invalidateCache($comment->post);
        });
    }

    private function guardPostCommentable(Post $post): void
    {
        if ($post->status !== 'published') {
            throw new UnprocessableEntityHttpException('This post is not available for comments.');
        }
    }

    private function guardCommentVotable(Comment $comment): void
    {
        if ($comment->is_deleted) {
            throw new NotFoundHttpException('Comment not found');
        }

        if ($comment->post->status !== 'published') {
            throw new UnprocessableEntityHttpException('This comment is not available for voting.');
        }
    }

    private function recalculateCommentVoteScore(Comment $comment): void
    {
        $totals = $this->voteRepository->countCommentVotes($comment->id);

        $comment->forceFill([
            'vote_score' => $totals['upvotes'] - $totals['downvotes'],
            'upvote_count' => $totals['upvotes'],
            'downvote_count' => $totals['downvotes'],
        ])->save();
    }

    private function invalidateCache(Post $post): void
    {
        $this->feedService->invalidate();
        Cache::forget("post:related:{$post->id}");
        Cache::forget("post:navigation:{$post->id}");
    }
}
