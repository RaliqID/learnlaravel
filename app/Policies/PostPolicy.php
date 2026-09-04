<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Post $post): bool
    {
        if ($post->status === 'published') {
            return true;
        }

        if ($user === null) {
            return false;
        }

        if ($user->role === 'admin' || $user->role === 'moderator') {
            return true;
        }

        return $user->id === $post->user_id;
    }

    public function create(User $user): bool
    {
        return !$user->is_banned;
    }

    public function update(User $user, Post $post): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->id === $post->user_id && !$post->is_locked;
    }

    public function delete(User $user, Post $post): bool
    {
        if ($user->role === 'admin' || $user->role === 'moderator') {
            return true;
        }

        return $user->id === $post->user_id;
    }

    public function restore(User $user, Post $post): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->id === $post->user_id;
    }

    public function forceDelete(User $user, Post $post): bool
    {
        return $user->role === 'admin';
    }

    public function vote(User $user, Post $post): bool
    {
        if ($user->is_banned) {
            return false;
        }

        return $post->status === 'published' && !$post->is_locked && $post->is_approved;
    }
}
