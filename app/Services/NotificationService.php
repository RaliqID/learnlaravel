<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\Notification;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Repositories\NotificationRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class NotificationService
{
    public function __construct(
        private readonly NotificationRepositoryInterface $repository
    ) {
    }

    public function listForUser(User $user, bool $unreadOnly, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->repository->listForUser($user->id, $unreadOnly, $page, $perPage);
    }

    public function unreadCount(User $user): int
    {
        return $this->repository->unreadCount($user->id);
    }

    public function markAsRead(User $user, Notification $notification): Notification
    {
        $this->repository->markAsRead($notification);

        return $notification->fresh();
    }

    public function markAllAsRead(User $user): int
    {
        return $this->repository->markAllAsRead($user->id);
    }

    public function delete(User $user, Notification $notification): bool
    {
        return $this->repository->delete($notification);
    }

    /**
     * Notify comment owner when their comment gets a reply.
     * Skip when the actor is the parent comment owner (self-reply).
     */
    public function notifyCommentReply(Comment $reply, int $actorId): void
    {
        if ($reply->parent_id === null) {
            return;
        }

        $parent = Comment::find($reply->parent_id);

        if (!$parent || $parent->user_id === $actorId) {
            return;
        }

        $this->send($parent->user_id, Notification::TYPE_COMMENT_REPLY, $parent, [
            'message' => 'Someone replied to your comment',
            'reply_comment_id' => $reply->id,
            'post_id' => $reply->post_id,
        ]);
    }

    /**
     * Notify post owner when a new top-level comment is posted.
     * Skip when the actor is the post owner.
     */
    public function notifyCommentPosted(Comment $comment, int $actorId): void
    {
        if ($comment->parent_id !== null) {
            return;
        }

        if ($comment->post->user_id === $actorId) {
            return;
        }

        $this->send($comment->post->user_id, Notification::TYPE_COMMENT_POSTED, $comment->post, [
            'message' => 'Someone commented on your post',
            'comment_id' => $comment->id,
            'post_id' => $comment->post_id,
        ]);
    }

    /**
     * Notify post owner when their post gets voted.
     * Skip when the voter is the post owner.
     */
    public function notifyPostVoted(Post $post, int $actorId): void
    {
        if ($post->user_id === $actorId) {
            return;
        }

        $this->send($post->user_id, Notification::TYPE_POST_VOTED, $post, [
            'message' => 'Someone voted on your post',
            'post_id' => $post->id,
        ]);
    }

    /**
     * Notify comment owner when their comment gets voted.
     * Skip when the voter is the comment owner.
     */
    public function notifyCommentVoted(Comment $comment, int $actorId): void
    {
        if ($comment->user_id === $actorId) {
            return;
        }

        $this->send($comment->user_id, Notification::TYPE_COMMENT_VOTED, $comment, [
            'message' => 'Someone voted on your comment',
            'comment_id' => $comment->id,
            'post_id' => $comment->post_id,
        ]);
    }

    /**
     * Notify a user when someone starts following them.
     * Skip when the follower is the followed user (self-follow).
     */
    public function notifyUserFollowed(User $followed, int $actorId): void
    {
        if ($followed->id === $actorId) {
            return;
        }

        $this->send($followed->id, Notification::TYPE_USER_FOLLOWED, $followed, [
            'message' => 'Someone started following you',
            'follower_user_id' => $actorId,
        ]);
    }

    /**
     * Notify a content owner that their content was removed by moderation.
     * Skip when the actor is the owner (moderator removing their own content).
     */
    public function notifyContentRemoved(User $owner, string $targetType, $target, int $actorId): void
    {
        if ($owner->id === $actorId) {
            return;
        }

        $this->send($owner->id, Notification::TYPE_CONTENT_REMOVED, $target, [
            'message' => 'Your content was removed by a moderator',
            'target_type' => $targetType,
            'target_id' => $target->getKey(),
        ]);
    }

    public function notifyUserBanned(User $user, int $actorId): void
    {
        if ($user->id === $actorId) {
            return;
        }

        $this->send($user->id, Notification::TYPE_USER_BANNED, $user, [
            'message' => 'Your account has been banned',
            'user_id' => $user->id,
        ]);
    }

    public function notifyUserUnbanned(User $user, int $actorId): void
    {
        if ($user->id === $actorId) {
            return;
        }

        $this->send($user->id, Notification::TYPE_USER_UNBANNED, $user, [
            'message' => 'Your account has been unbanned',
            'user_id' => $user->id,
        ]);
    }

    public function notifyReportResolved(Report $report, int $actorId): void
    {
        if ($report->reporter_id === $actorId) {
            return;
        }

        $this->send($report->reporter_id, Notification::TYPE_REPORT_RESOLVED, $report, [
            'message' => 'Your report has been reviewed',
            'report_id' => $report->id,
            'status' => $report->status,
        ]);
    }

    public function send(int $userId, string $type, $notifiable, array $data): void
    {
        $this->repository->create([
            'id' => (string) Str::uuid(),
            'user_id' => $userId,
            'type' => $type,
            'notifiable_type' => $notifiable->getMorphClass(),
            'notifiable_id' => $notifiable->getKey(),
            'data' => $data,
            'read_at' => null,
        ]);
    }
}
