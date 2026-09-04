<?php

namespace App\Repositories;

use App\Models\Post;
use App\Models\Topic;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class PostRepository implements PostRepositoryInterface
{
    private const LIST_COLUMNS = [
        'id',
        'user_id',
        'topic_id',
        'title',
        'slug',
        'content',
        'post_type',
        'image_url',
        'thumbnail_url',
        'vote_score',
        'comment_count',
        'view_count',
        'hot_score',
        'is_pinned',
        'is_locked',
        'is_nsfw',
        'is_approved',
        'status',
        'featured_at',
        'featured_by',
        'published_at',
        'last_activity_at',
        'source_name',
        'source_url',
        'created_at',
        'updated_at',
    ];

    private const LIST_WITH = [
        'user:id,username,avatar,display_name',
        'topic:id,name,slug',
    ];

    private const DETAIL_WITH = [
        'user:id,username,avatar,display_name',
        'topic:id,name,slug',
    ];

    public function getFeed(array $filters, int $page, int $perPage): LengthAwarePaginator
    {
        $query = $this->publicBaseQuery($filters['source'] ?? null)
            ->fromTimeRange($filters['time'] ?? 'all');

        if (!empty($filters['topic_id'])) {
            $query->where('topic_id', $filters['topic_id']);
        }

        $this->applySort($query, $filters['sort'] ?? 'new');

        return $query->paginate($perPage, self::LIST_COLUMNS, 'page', $page);
    }

    public function getHero(): array
    {
        $excludedIds = [];

        $featured = $this->publicBaseQuery()
            ->featured()
            ->orderByDesc('featured_at')
            ->first(self::LIST_COLUMNS);

        if ($featured) {
            $excludedIds[] = $featured->id;
        }

        $breaking = $this->publicBaseQuery()
            ->breaking()
            ->whereNotIn('id', $excludedIds)
            ->orderByDesc('published_at')
            ->first(self::LIST_COLUMNS);

        if ($breaking) {
            $excludedIds[] = $breaking->id;
        }

        $velocityTrending = $this->getVelocityTrending(1)
            ->whereNotIn('id', $excludedIds)
            ->first();

        if ($velocityTrending) {
            $excludedIds[] = $velocityTrending->id;
        }

        $latest = $this->publicBaseQuery()
            ->whereNotIn('id', $excludedIds)
            ->sortNew()
            ->limit(5)
            ->get(self::LIST_COLUMNS);

        $headline = $featured ?? $breaking ?? $velocityTrending ?? $latest->shift();

        return [
            'headline' => $headline,
            'secondary' => $latest->take(4)->values(),
        ];
    }

    public function getTrending(string $time = 'day', int $limit = 10, ?string $source = null): Collection
    {
        return $this->publicBaseQuery($source)
            ->fromTimeRange($time)
            ->where('hot_score', '>', 0)
            ->sortHot()
            ->limit($limit)
            ->get(self::LIST_COLUMNS);
    }

    public function getEditorsPicks(int $limit = 5, ?string $source = null): Collection
    {
        return $this->publicBaseQuery($source)
            ->featured()
            ->orderByDesc('featured_at')
            ->limit($limit)
            ->get(self::LIST_COLUMNS);
    }

    public function getPopularTopics(int $limit = 10): Collection
    {
        return Topic::query()
            ->where('is_active', true)
            ->orderByDesc('post_count')
            ->limit($limit)
            ->get(['id', 'name', 'slug', 'description', 'icon', 'post_count', 'subscriber_count']);
    }

    public function getPostsByTopic(int $topicId, int $limit, ?string $source = null): Collection
    {
        return $this->publicBaseQuery($source)
            ->where('topic_id', $topicId)
            ->orderByDesc('hot_score')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get(self::LIST_COLUMNS);
    }

    public function getRecommended(int $limit = 6, ?string $source = null): Collection
    {
        $pool = collect();
        $pool = $pool->merge($this->getTrending('week', 10, $source));
        $pool = $pool->merge($this->getEditorsPicks(5, $source));
        $pool = $pool->merge($this->publicBaseQuery($source)->sortNew()->limit(10)->get(self::LIST_COLUMNS));
        $pool = $pool->merge($this->publicBaseQuery($source)->sortTop()->limit(10)->get(self::LIST_COLUMNS));

        return new Collection($pool->unique('id')->values()->take($limit)->all());
    }

    public function findById(int $id): ?Post
    {
        return Post::with(self::DETAIL_WITH)->find($id);
    }

    public function findBySlug(string $slug): ?Post
    {
        return Post::with(self::DETAIL_WITH)->where('slug', $slug)->first();
    }

    public function findDuplicateUrl(string $url, ?int $excludePostId = null): ?Post
    {
        return Post::query()
            ->where('url', $url)
            ->where('status', 'published')
            ->when($excludePostId, fn ($q) => $q->where('id', '!=', $excludePostId))
            ->first(['id', 'title', 'slug', 'published_at']);
    }

    public function getUserPosts(int $userId, string $status, int $page, int $perPage): LengthAwarePaginator
    {
        return Post::query()
            ->with(self::LIST_WITH)
            ->where('user_id', $userId)
            ->where('status', $status)
            ->orderByDesc('created_at')
            ->paginate($perPage, self::LIST_COLUMNS, 'page', $page);
    }

    public function searchPosts(array $filters, int $page, int $perPage): LengthAwarePaginator
    {
        $query = $this->publicBaseQuery()
            ->fromTimeRange($filters['time'] ?? 'all');

        if (!empty($filters['topic_id'])) {
            $query->where('topic_id', $filters['topic_id']);
        }

        $query->whereFullText(['title', 'content'], $filters['q'], ['mode' => 'boolean']);

        $this->applySearchSort($query, $filters['sort'] ?? 'relevance', $filters['q']);

        return $query->paginate($perPage, self::LIST_COLUMNS, 'page', $page);
    }

    public function getUserPublicPosts(int $userId, int $page, int $perPage): LengthAwarePaginator
    {
        return $this->publicBaseQuery()
            ->where('user_id', $userId)
            ->sortNew()
            ->paginate($perPage, self::LIST_COLUMNS, 'page', $page);
    }

    public function create(array $data): Post
    {
        return Post::create($data);
    }

    public function update(Post $post, array $data): Post
    {
        $post->update($data);

        return $post->fresh(self::DETAIL_WITH);
    }

    public function delete(Post $post): bool
    {
        return (bool) $post->delete();
    }

    public function restore(Post $post): bool
    {
        return (bool) $post->restore();
    }

    public function isSlugTaken(string $slug, ?int $excludeId = null): bool
    {
        return Post::withTrashed()
            ->where('slug', $slug)
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
    }

    public function incrementViewCount(Post $post): void
    {
        $post->increment('view_count');
    }

    public function relatedPosts(Post $post, int $limit = 6): Collection
    {
        return $this->publicBaseQuery()
            ->where('topic_id', $post->topic_id)
            ->where('id', '!=', $post->id)
            ->orderByDesc('hot_score')
            ->orderByDesc('published_at')
            ->limit($limit)
            ->get(self::LIST_COLUMNS);
    }

    public function previousPost(Post $post): ?Post
    {
        return $this->publicBaseQuery()
            ->where('published_at', '<', $post->published_at)
            ->orderByDesc('published_at')
            ->first(self::LIST_COLUMNS);
    }

    public function nextPost(Post $post): ?Post
    {
        return $this->publicBaseQuery()
            ->where('published_at', '>', $post->published_at)
            ->orderBy('published_at')
            ->first(self::LIST_COLUMNS);
    }

    /**
     * Get trending posts with emphasis on engagement velocity.
     * Used for hero/breaking selection.
     */
    /**
     * Get trending posts with emphasis on engagement velocity.
     * Used for hero/breaking selection.
     */
    private function getVelocityTrending(int $limit = 5, ?string $source = null): Collection
    {
        return $this->publicBaseQuery($source)
            ->where('hot_score', '>', 0)
            ->sortHot()
            ->limit($limit)
            ->get(self::LIST_COLUMNS);
    }

    private function publicBaseQuery(?string $source = null): Builder
    {
        $query = Post::query()
            ->with(self::LIST_WITH)
            ->published()
            ->approved()
            ->visible();

        if ($source === 'news') {
            $query->whereNotNull('source_name');
        } elseif ($source === 'community') {
            $query->whereNull('source_name');
        }

        return $query;
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'hot' => $query->sortHot(),
            'top' => $query->sortTop(),
            default => $query->sortNew(),
        };
    }

    private function applySearchSort(Builder $query, string $sort, string $term): void
    {
        match ($sort) {
            'newest' => $query->sortNew(),
            'oldest' => $query->orderBy('published_at'),
            'popular' => $query->sortHot(),
            default => $query->orderByRaw('MATCH (title, content) AGAINST (? IN BOOLEAN MODE) DESC', [$term]),
        };
    }

    public function getActivityChart(): array
    {
        // Get last 7 days of published posts grouped by day
        $data = Post::query()
            ->selectRaw('DATE(published_at) as date, COUNT(*) as count')
            ->where('status', 'published')
            ->where('published_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Fill missing days with 0
        $result = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $dayName = now()->subDays($i)->format('D'); // Mon, Tue, etc
            
            $found = $data->firstWhere('date', $date);
            $result[] = [
                'day' => $dayName,
                'date' => $date,
                'count' => $found ? $found->count : 0,
            ];
        }

        return [
            'labels' => array_column($result, 'day'),
            'data' => array_column($result, 'count'),
            'dates' => array_column($result, 'date'),
        ];
    }

    public function getCategoryDistribution(int $days): array
    {
        $startDate = now()->subDays($days)->startOfDay();
        
        $posts = Post::query()
            ->select(['topic_id', 'published_at'])
            ->where('status', 'published')
            ->where('published_at', '>=', $startDate)
            ->with('topic:id,name')
            ->get();

        $grouped = [];
        if ($days === 1) {
            // Hourly aggregation for 1D view
            $labels = [];
            for ($i = 0; $i < 24; $i++) {
                $labels[] = now()->subHours(23 - $i)->format('H:00');
            }
            $dateLabels = $labels;
            foreach ($posts as $post) {
                $hour = $post->published_at->format('H:00');
                $topicName = $post->topic?->name ?? 'Uncategorized';
                if (!isset($grouped[$topicName])) {
                    $grouped[$topicName] = array_fill_keys($dateLabels, 0);
                }
                if (isset($grouped[$topicName][$hour])) {
                    $grouped[$topicName][$hour]++;
                }
            }
        } else {
            // Daily aggregation
            $dateLabels = [];
            $current = $startDate->copy();
            while ($current <= now()) {
                $dateLabels[] = $current->format('Y-m-d');
                $current->addDay();
            }
            foreach ($posts as $post) {
                $date = $post->published_at->format('Y-m-d');
                $topicName = $post->topic?->name ?? 'Uncategorized';
                if (!isset($grouped[$topicName])) {
                    $grouped[$topicName] = array_fill_keys($dateLabels, 0);
                }
                if (isset($grouped[$topicName][$date])) {
                    $grouped[$topicName][$date]++;
                }
            }
        }

        // Sort topics by total count (desc) and keep top 6, rest as "Others"
        $topicTotals = [];
        foreach ($grouped as $topic => $counts) {
            $topicTotals[$topic] = array_sum($counts);
        }
        arsort($topicTotals);
        $topTopics = array_slice(array_keys($topicTotals), 0, 6);
        $othersTopics = array_slice(array_keys($topicTotals), 6);

        $finalGrouped = [];
        foreach ($topTopics as $topic) {
            $finalGrouped[$topic] = $grouped[$topic];
        }
        if (!empty($othersTopics)) {
            $othersCounts = array_fill_keys($dateLabels, 0);
            foreach ($othersTopics as $topic) {
                foreach ($grouped[$topic] as $date => $count) {
                    $othersCounts[$date] += $count;
                }
            }
            $finalGrouped['Others'] = $othersCounts;
        }

        $colors = [
            '#3b82f6', '#ef4444', '#22c55e', '#f59e0b', '#8b5cf6',
            '#ec4899', '#14b8a6', '#f97316', '#6366f1', '#84cc16',
        ];
        $datasets = [];
        $idx = 0;
        foreach ($finalGrouped as $topic => $counts) {
            $datasets[] = [
                'label' => $topic,
                'data' => array_values($counts),
                'backgroundColor' => $colors[$idx % count($colors)],
                'borderColor' => $colors[$idx % count($colors)],
            ];
            $idx++;
        }

        return [
            'labels' => $dateLabels,
            'datasets' => $datasets,
        ];
    }
}
