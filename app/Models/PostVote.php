<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostVote extends Model
{
    use HasFactory;

    public const UPVOTE = 1;
    public const DOWNVOTE = -1;

    protected $fillable = [
        'post_id',
        'user_id',
        'vote_type',
    ];

    protected $casts = [
        'vote_type' => 'integer',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
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
