<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Post extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'topic_id',
        'title',
        'slug',
        'content',
        'content_html',
        'url',
        'canonical_url',
        'meta_title',
        'meta_description',
        'post_type',
        'image_url',
        'thumbnail_url',
        'vote_score',
        'upvote_count',
        'downvote_count',
        'comment_count',
        'view_count',
        'bookmark_count',
        'hot_score',
        'controversy_score',
        'is_pinned',
        'is_locked',
        'is_nsfw',
        'is_approved',
        'status',
        'featured_at',
        'featured_by',
        'published_at',
        'edited_at',
        'last_activity_at',
        'source_name',
        'source_url',
    ];

    protected $casts = [
        'vote_score' => 'integer',
        'upvote_count' => 'integer',
        'downvote_count' => 'integer',
        'comment_count' => 'integer',
        'view_count' => 'integer',
        'bookmark_count' => 'integer',
        'hot_score' => 'decimal:4',
        'controversy_score' => 'decimal:4',
        'is_pinned' => 'boolean',
        'is_locked' => 'boolean',
        'is_nsfw' => 'boolean',
        'is_approved' => 'boolean',
        'featured_at' => 'datetime',
        'published_at' => 'datetime',
        'edited_at' => 'datetime',
        'last_activity_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function featuredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'featured_by');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(PostVote::class);
    }

    public function voters(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'post_votes')
            ->withPivot('vote_type')
            ->withTimestamps();
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    public function getExcerptAttribute(): string
    {
        return Str::limit(strip_tags((string) $this->content), 180);
    }

    public function getIsFeaturedAttribute(): bool
    {
        return $this->featured_at !== null;
    }

    public function getIsPublishedAttribute(): bool
    {
        return $this->status === 'published';
    }

    public function getReadingTimeAttribute(): int
    {
        $cleaned = preg_replace('/[#>*_`~\[\]()!-]/', ' ', (string) $this->content);
        $words = str_word_count((string) $cleaned);

        return max(1, (int) ceil($words / 200));
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', 'archived');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('is_approved', true);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_locked', false);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->whereNotNull('featured_at');
    }

    public function scopeBreaking(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

    public function scopeSortHot(Builder $query): Builder
    {
        return $query->orderByDesc('hot_score');
    }

    public function scopeSortNew(Builder $query): Builder
    {
        return $query->orderByDesc('published_at');
    }

    public function scopeSortTop(Builder $query): Builder
    {
        return $query->orderByDesc('vote_score');
    }

    public function scopeFromTimeRange(Builder $query, string $range): Builder
    {
        return match ($range) {
            'hour' => $query->where('published_at', '>=', now()->subHour()),
            'day' => $query->where('published_at', '>=', now()->subDay()),
            'week' => $query->where('published_at', '>=', now()->subWeek()),
            'month' => $query->where('published_at', '>=', now()->subMonth()),
            'year' => $query->where('published_at', '>=', now()->subYear()),
            default => $query,
        };
    }
}
