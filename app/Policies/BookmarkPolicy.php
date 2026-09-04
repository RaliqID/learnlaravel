<?php

namespace App\Policies;

use App\Models\Bookmark;
use App\Models\Post;
use App\Models\User;

class BookmarkPolicy
{
    public function create(User $user, Post $post): bool
    {
        return !$user->is_banned && $post->status === 'published';
    }

    public function delete(User $user, Bookmark $bookmark): bool
    {
        return $user->id === $bookmark->user_id || $user->role === 'admin';
    }
}
