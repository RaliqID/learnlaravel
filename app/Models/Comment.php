<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'post_id',
        'parent_id',
        'content',
        'vote_score',
        'upvote_count',
        'downvote_count',
        'reply_count',
        'depth',
        'is_deleted',
        'edited_at',
    ];

    protected $casts = [
        'vote_score' => 'integer',
        'upvote_count' => 'integer',
        'downvote_count' => 'integer',
        'reply_count' => 'integer',
        'depth' => 'integer',
        'is_deleted' => 'boolean',
        'edited_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Get comments which have this comment as direct parent.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->where('is_deleted', false)->orderBy('created_at', 'asc');
    }

    /**
     * Get all descendants of this comment.
     */
    public function allReplies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_deleted', false);
    }

    public function scopeSortNew($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeSortOld($query)
    {
        return $query->orderBy('created_at');
    }

    public function scopeSortTop($query)
    {
        return $query->orderByDesc('vote_score');
    }

    public function scopeByPost($query)
    {
        return $query->where('post_id', '!=', null);
    }

    /**
     * Increment reply count for parent comment.
     */
    public static function incrementReplyCount(int $commentId): void
    {
        if ($commentId > 0) {
            static::where('id', $commentId)->increment('reply_count');
        }
    }

    /**
     * Decrement reply count for parent comment.
     */
    public static function decrementReplyCount(int $commentId): void
    {
        if ($commentId > 0) {
            static::where('id', $commentId)->decrement('reply_count')->first();
        }
    }
}
