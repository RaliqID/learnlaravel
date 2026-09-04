<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Topic extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'banner',
        'subscriber_count',
        'post_count',
        'is_active',
        'is_private',
        'requires_approval',
        'rules',
    ];

    protected $casts = [
        'subscriber_count' => 'integer',
        'post_count' => 'integer',
        'is_active' => 'boolean',
        'is_private' => 'boolean',
        'requires_approval' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saving(function (Topic $topic) {
            if (empty($topic->slug)) {
                $topic->slug = Str::slug($topic->name);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'topic_subscriptions')
            ->withPivot('notification_enabled', 'created_at');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
