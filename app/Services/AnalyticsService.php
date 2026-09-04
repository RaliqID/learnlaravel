<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    private const PERIOD_DAYS = [
        'today' => 1,
        '7d' => 7,
        '30d' => 30,
        '90d' => 90,
        'all' => null,
    ];

    /**
     * Resolve a period key to a starting Carbon timestamp (or null for "all").
     */
    public function resolveStart(string $period): ?Carbon
    {
        $days = self::PERIOD_DAYS[$period] ?? null;

        if ($days === null) {
            return null;
        }

        return $days === 1 ? now()->startOfDay() : now()->subDays($days);
    }

    public function overview(string $period): array
    {
        $start = $this->resolveStart($period);

        $userQuery = DB::table('users');
        $postQuery = DB::table('posts');
        $commentQuery = DB::table('comments');

        if ($start) {
            $userQuery->where('created_at', '>=', $start);
            $postQuery->where('created_at', '>=', $start);
            $commentQuery->where('created_at', '>=', $start);
        }

        $users = $userQuery->count();
        $posts = $postQuery->count();
        $comments = $commentQuery->count();

        $votes = DB::table('post_votes')
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->count();

        $commentVotes = DB::table('comment_votes')
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->count();

        $bookmarks = DB::table('bookmarks')
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->count();

        $follows = DB::table('follows')
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->count();

        $reports = DB::table('reports')
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->count();

        $moderationActions = DB::table('moderation_logs')
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->count();

        return [
            'users' => $users,
            'posts' => $posts,
            'comments' => $comments,
            'post_votes' => $votes,
            'comment_votes' => $commentVotes,
            'bookmarks' => $bookmarks,
            'follows' => $follows,
            'reports' => $reports,
            'moderation_actions' => $moderationActions,
            'period' => $period,
        ];
    }

    public function userActivity(string $period, string $granularity = 'day'): array
    {
        $start = $this->resolveStart($period);

        $dateExpr = $granularity === 'month' ? 'DATE_FORMAT(created_at, "%Y-%m")' : 'DATE(created_at)';

        $users = DB::table('users')
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->groupBy(DB::raw($dateExpr))
            ->orderBy(DB::raw($dateExpr))
            ->select(DB::raw($dateExpr.' as bucket'), DB::raw('COUNT(*) as total'))
            ->get();

        $posts = DB::table('posts')
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->groupBy(DB::raw($dateExpr))
            ->orderBy(DB::raw($dateExpr))
            ->select(DB::raw($dateExpr.' as bucket'), DB::raw('COUNT(*) as total'))
            ->get();

        $comments = DB::table('comments')
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->groupBy(DB::raw($dateExpr))
            ->orderBy(DB::raw($dateExpr))
            ->select(DB::raw($dateExpr.' as bucket'), DB::raw('COUNT(*) as total'))
            ->get();

        $postVotes = DB::table('post_votes')
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->groupBy(DB::raw($dateExpr))
            ->orderBy(DB::raw($dateExpr))
            ->select(DB::raw($dateExpr.' as bucket'), DB::raw('COUNT(*) as total'))
            ->get();

        return [
            'period' => $period,
            'granularity' => $granularity,
            'series' => [
                'users' => $users,
                'posts' => $posts,
                'comments' => $comments,
                'post_votes' => $postVotes,
            ],
        ];
    }

    public function contentPerformance(string $period, int $limit = 10): array
    {
        $start = $this->resolveStart($period);

        $base = fn ($q) => $q
            ->whereNull('deleted_at')
            ->where('status', 'published')
            ->when($start, fn ($query) => $query->where('published_at', '>=', $start));

        $topViewed = DB::table('posts')
            ->where(fn ($q) => $base($q))
            ->orderByDesc('view_count')
            ->limit($limit)
            ->get(['id', 'title', 'slug', 'view_count']);

        $topVoted = DB::table('posts')
            ->where(fn ($q) => $base($q))
            ->orderByDesc('vote_score')
            ->limit($limit)
            ->get(['id', 'title', 'slug', 'vote_score']);

        $topBookmarked = DB::table('posts')
            ->where(fn ($q) => $base($q))
            ->orderByDesc('bookmark_count')
            ->limit($limit)
            ->get(['id', 'title', 'slug', 'bookmark_count']);

        $topCommented = DB::table('posts')
            ->where(fn ($q) => $base($q))
            ->orderByDesc('comment_count')
            ->limit($limit)
            ->get(['id', 'title', 'slug', 'comment_count']);

        $topTopics = DB::table('topics')
            ->where('is_active', true)
            ->orderByDesc('post_count')
            ->limit($limit)
            ->get(['id', 'name', 'slug', 'post_count', 'subscriber_count']);

        $totalViews = DB::table('posts')
            ->where(fn ($q) => $base($q))
            ->sum('view_count');

        return [
            'period' => $period,
            'total_views' => (int) $totalViews,
            'most_viewed' => $topViewed,
            'most_voted' => $topVoted,
            'most_bookmarked' => $topBookmarked,
            'most_commented' => $topCommented,
            'top_topics' => $topTopics,
        ];
    }

    public function moderation(string $period): array
    {
        $start = $this->resolveStart($period);

        $reportQuery = DB::table('reports')
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start));

        $reportCounts = (clone $reportQuery)
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status IN ('pending', 'reviewing') THEN 1 ELSE 0 END) as open")
            ->selectRaw("SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved")
            ->selectRaw("SUM(CASE WHEN status = 'dismissed' THEN 1 ELSE 0 END) as dismissed")
            ->first();

        $byType = (clone $reportQuery)
            ->select('reportable_type', DB::raw('COUNT(*) as total'))
            ->groupBy('reportable_type')
            ->get();

        $logQuery = DB::table('moderation_logs')
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start));

        $actions = (clone $logQuery)->count();

        $byAction = (clone $logQuery)
            ->select('action', DB::raw('COUNT(*) as total'))
            ->groupBy('action')
            ->get();

        $overTime = DB::table('moderation_logs')
            ->when($start, fn ($q) => $q->where('created_at', '>=', $start))
            ->select(DB::raw('DATE(created_at) as bucket'), DB::raw('COUNT(*) as total'))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy(DB::raw('DATE(created_at)'))
            ->get();

        return [
            'period' => $period,
            'reports' => [
                'total' => (int) ($reportCounts->total ?? 0),
                'open' => (int) ($reportCounts->open ?? 0),
                'resolved' => (int) ($reportCounts->resolved ?? 0),
                'dismissed' => (int) ($reportCounts->dismissed ?? 0),
                'by_type' => $byType,
            ],
            'moderation_actions' => [
                'total' => (int) $actions,
                'by_action' => $byAction,
            ],
            'over_time' => $overTime,
        ];
    }
}
