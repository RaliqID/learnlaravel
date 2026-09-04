<?php

namespace App\Services;

use App\Repositories\UserRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;

class AdminService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function dashboard(): array
    {
        $userStats = DB::table('users')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_banned = 0 OR (is_banned = 1 AND banned_until IS NOT NULL AND banned_until < ?) THEN 1 ELSE 0 END) as active', [now()])
            ->selectRaw('SUM(CASE WHEN is_banned = 1 AND (banned_until IS NULL OR banned_until >= ?) THEN 1 ELSE 0 END) as banned', [now()])
            ->selectRaw('SUM(CASE WHEN email_verified_at IS NOT NULL THEN 1 ELSE 0 END) as verified')
            ->first();

        $postStats = DB::table('posts')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'published' AND deleted_at IS NULL THEN 1 ELSE 0 END) as published")
            ->selectRaw("SUM(CASE WHEN status = 'draft' AND deleted_at IS NULL THEN 1 ELSE 0 END) as draft")
            ->selectRaw("SUM(CASE WHEN status = 'archived' AND deleted_at IS NULL THEN 1 ELSE 0 END) as archived")
            ->selectRaw('SUM(CASE WHEN deleted_at IS NOT NULL THEN 1 ELSE 0 END) as removed')
            ->first();

        $commentStats = DB::table('comments')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN is_deleted = 0 AND deleted_at IS NULL THEN 1 ELSE 0 END) as active')
            ->selectRaw('SUM(CASE WHEN is_deleted = 1 OR deleted_at IS NOT NULL THEN 1 ELSE 0 END) as removed')
            ->first();

        $reportStats = DB::table('reports')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status IN ('pending', 'reviewing') THEN 1 ELSE 0 END) as open")
            ->selectRaw("SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved")
            ->selectRaw("SUM(CASE WHEN status = 'dismissed' THEN 1 ELSE 0 END) as dismissed")
            ->first();

        $topContent = DB::table('posts')
            ->select('id', 'title', 'slug', 'vote_score', 'view_count', 'comment_count', 'hot_score', 'status', 'published_at')
            ->orderByDesc('vote_score')
            ->limit(5)
            ->get();

        $recentActions = DB::table('moderation_logs')
            ->select('id', 'moderator_id', 'action', 'target_type', 'target_id', 'reason', 'metadata', 'created_at')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        return [
            'users' => [
                'total' => (int) $userStats->total,
                'active' => (int) $userStats->active,
                'banned' => (int) $userStats->banned,
                'verified' => (int) $userStats->verified,
            ],
            'posts' => [
                'total' => (int) $postStats->total,
                'published' => (int) $postStats->published,
                'draft' => (int) $postStats->draft,
                'archived' => (int) $postStats->archived,
                'removed' => (int) $postStats->removed,
            ],
            'comments' => [
                'total' => (int) $commentStats->total,
                'active' => (int) $commentStats->active,
                'removed' => (int) $commentStats->removed,
            ],
            'reports' => [
                'total' => (int) $reportStats->total,
                'open' => (int) $reportStats->open,
                'resolved' => (int) $reportStats->resolved,
                'dismissed' => (int) $reportStats->dismissed,
            ],
            'top_content' => $topContent,
            'recent_moderation_actions' => $recentActions,
        ];
    }

    public function listUsers(int $page = 1, int $perPage = 20, ?string $role = null, ?string $search = null, ?bool $banned = null): LengthAwarePaginator
    {
        return $this->userRepository->all($page, $perPage, $role, $search, $banned);
    }
}
