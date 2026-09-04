<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentVote extends Model
{
    use HasFactory;

    public const UPVOTE = 1;
    public const DOWNVOTE = -1;

    protected $fillable = ['comment_id', 'user_id', 'vote_type'];

    protected $casts = ['vote_type' => 'integer'];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(Comment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUpvote($query)
    {
        return $query->where('vote_type', self::UPVOTE);
    }

    public function scopeDownvote($query)
    {
        return $query->where('vote_type', self::DOWNVOTE);
    }
}
