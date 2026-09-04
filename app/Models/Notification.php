<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class Notification extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    public const TYPE_COMMENT_REPLY = 'CommentReply';
    public const TYPE_COMMENT_POSTED = 'CommentPosted';
    public const TYPE_POST_VOTED = 'PostVoted';
    public const TYPE_COMMENT_VOTED = 'CommentVoted';
    public const TYPE_USER_FOLLOWED = 'UserFollowed';
    public const TYPE_CONTENT_REMOVED = 'ContentRemoved';
    public const TYPE_USER_BANNED = 'UserBanned';
    public const TYPE_USER_UNBANNED = 'UserUnbanned';
    public const TYPE_REPORT_RESOLVED = 'ReportResolved';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }

    public function markAsUnread(): void
    {
        $this->forceFill(['read_at' => null])->save();
    }

    public function getIsReadAttribute(): bool
    {
        return $this->read_at !== null;
    }

    protected static function booted(): void
    {
        static::creating(function (Notification $notification) {
            if (empty($notification->id)) {
                $notification->id = (string) Str::uuid();
            }
        });
    }
}
