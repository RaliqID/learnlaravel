<?php

namespace App\Policies;

use App\Models\Topic;
use App\Models\User;

class TopicPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Topic $topic): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Topic $topic): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, Topic $topic): bool
    {
        return $user->role === 'admin';
    }

    public function restore(User $user, Topic $topic): bool
    {
        return $user->role === 'admin';
    }

    public function forceDelete(User $user, Topic $topic): bool
    {
        return $user->role === 'admin';
    }
}
