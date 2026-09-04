<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\ModerationLog;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use App\Repositories\ReportRepositoryInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ModerationService
{
    public function __construct(
        private readonly ReportRepositoryInterface $reportRepository,
        private readonly PostService $postService,
        private readonly CommentService $commentService,
        private readonly NotificationService $notificationService,
    ) {
    }

    // ------------------------------------------------------------------
    // Reports
    // ------------------------------------------------------------------

    public function submitReport(User $reporter, array $data): Report
    {
        [$targetClass, $target] = $this->resolveReportable($data['reportable_type'], (int) $data['reportable_id']);

        if ($this->reportRepository->hasOpenReportBy($reporter->id, $targetClass, $target->getKey())) {
            throw new ConflictHttpException('You have already reported this content.');
        }

        return $this->reportRepository->create([
            'reporter_id' => $reporter->id,
            'reportable_type' => $targetClass,
            'reportable_id' => $target->getKey(),
            'reason' => $data['reason'],
            'description' => $data['description'] ?? null,
            'status' => Report::STATUS_PENDING,
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function queue(array $filters, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->reportRepository->listForModeration($filters, $page, $perPage);
    }

    public function reportDetail(Report $report): Report
    {
        return $this->reportRepository->find($report->id) ?? $report->load(['reporter', 'reportable']);
    }

    public function resolveReport(User $moderator, Report $report, string $status, ?string $resolutionNote): Report
    {
        return DB::transaction(function () use ($moderator, $report, $status, $resolutionNote) {
            if (!$report->isOpen()) {
                throw new UnprocessableEntityHttpException('This report has already been resolved.');
            }

            $resolved = $this->reportRepository->update($report, [
                'status' => $status,
                'resolved_by' => $moderator->id,
                'resolved_at' => now(),
                'resolution_note' => $resolutionNote,
            ]);

            $this->log($moderator, 'report:'.$status, $report, $resolutionNote);

            $this->notificationService->notifyReportResolved($resolved, $moderator->id);

            return $resolved;
        });
    }

    // ------------------------------------------------------------------
    // Content removal / restore
    // ------------------------------------------------------------------

    public function removePost(User $moderator, Post $post): Post
    {
        $this->ensureModerator($moderator);

        return DB::transaction(function () use ($moderator, $post) {
            if ($post->trashed()) {
                throw new UnprocessableEntityHttpException('This post has already been removed.');
            }

            $this->postService->destroy($post);

            $this->log($moderator, 'post:remove', $post, null);

            $this->notificationService->notifyContentRemoved($post->user, 'post', $post, $moderator->id);

            return $post->fresh();
        });
    }

    public function restorePost(User $moderator, Post $post): Post
    {
        $this->ensureModerator($moderator);

        return DB::transaction(function () use ($moderator, $post) {
            if (!$post->trashed()) {
                throw new UnprocessableEntityHttpException('This post is not removed.');
            }

            $this->postService->restore($post);

            $this->log($moderator, 'post:restore', $post, null);

            return $post->fresh();
        });
    }

    public function removeComment(User $moderator, Comment $comment): Comment
    {
        $this->ensureModerator($moderator);

        return DB::transaction(function () use ($moderator, $comment) {
            if ($comment->trashed()) {
                throw new UnprocessableEntityHttpException('This comment has already been removed.');
            }

            $this->commentService->deleteComment($comment, $moderator);

            $this->log($moderator, 'comment:remove', $comment, null);

            $this->notificationService->notifyContentRemoved($comment->user, 'comment', $comment, $moderator->id);

            return $comment->fresh();
        });
    }

    public function restoreComment(User $moderator, Comment $comment): Comment
    {
        $this->ensureModerator($moderator);

        return DB::transaction(function () use ($moderator, $comment) {
            if (!$comment->trashed()) {
                throw new UnprocessableEntityHttpException('This comment is not removed.');
            }

            $restored = $this->commentService->restoreComment($comment, $moderator);

            $this->log($moderator, 'comment:restore', $comment, null);

            return $restored;
        });
    }

    // ------------------------------------------------------------------
    // User ban / unban
    // ------------------------------------------------------------------

    public function banUser(User $moderator, User $target, string $reason, ?int $durationDays): User
    {
        return DB::transaction(function () use ($moderator, $target, $reason, $durationDays) {
            $banned = $this->applyBan($target, $reason, $durationDays);

            $this->log($moderator, 'user:ban', $target, $reason, [
                'duration_days' => $durationDays,
            ]);

            $this->notificationService->notifyUserBanned($target, $moderator->id);

            return $banned->fresh();
        });
    }

    public function unbanUser(User $moderator, User $target): User
    {
        return DB::transaction(function () use ($moderator, $target) {
            $target->forceFill([
                'is_banned' => false,
                'banned_until' => null,
                'banned_reason' => null,
            ])->save();

            $this->log($moderator, 'user:unban', $target, null);

            $this->notificationService->notifyUserUnbanned($target, $moderator->id);

            return $target->fresh();
        });
    }

    private function applyBan(User $target, string $reason, ?int $durationDays): User
    {
        $target->forceFill([
            'is_banned' => true,
            'banned_until' => $durationDays !== null ? now()->addDays($durationDays) : null,
            'banned_reason' => $reason,
        ])->save();

        return $target;
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function resolveReportable(string $type, int $id): array
    {
        if ($type === 'post') {
            $target = Post::query()->find($id);
            if (!$target || $target->trashed()) {
                throw new NotFoundHttpException('Post not found');
            }

            return [Post::class, $target];
        }

        $target = Comment::query()->find($id);
        if (!$target || $target->trashed() || $target->is_deleted) {
            throw new NotFoundHttpException('Comment not found');
        }

        return [Comment::class, $target];
    }

    private function ensureModerator(User $user): void
    {
        if ($user->role !== 'admin' && $user->role !== 'moderator') {
            throw new AuthorizationException('You are not authorized to perform moderation actions.');
        }
    }

    private function log(User $moderator, string $action, $target, ?string $reason, array $metadata = []): void
    {
        ModerationLog::create([
            'moderator_id' => $moderator->id,
            'action' => $action,
            'target_type' => $target->getMorphClass(),
            'target_id' => $target->getKey(),
            'reason' => $reason,
            'metadata' => $metadata,
        ]);
    }
}
