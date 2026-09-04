<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Comment $comment): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return !$user->is_banned;
    }

    public function update(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id;
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id || $user->role === 'admin' || $user->role === 'moderator';
    }

    public function vote(User $user, Comment $comment): bool
    {
        if ($comment->is_deleted) {
            return false;
        }

        if ($user->is_banned) {
            return false;
        }

        return $comment->post->status === 'published';
    }

    public function forceDelete(User $user, Comment $comment): bool
    {
        return $user->role === 'admin';
    }
}
