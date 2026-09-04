<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, User $model): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, User $model): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return $user->id === $model->id;
    }

    public function restore(User $user, User $model): bool
    {
        return $user->role === 'admin';
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->role === 'admin';
    }

    public function ban(User $user, User $model): bool
    {
        if ($user->role !== 'admin' && $user->role !== 'moderator') {
            return false;
        }

        if ($model->role === 'admin') {
            return false;
        }

        return $user->id !== $model->id;
    }

    public function unban(User $user, User $model): bool
    {
        return $user->role === 'admin' || $user->role === 'moderator';
    }

    public function updateRole(User $user, User $model): bool
    {
        if ($user->role !== 'admin') {
            return false;
        }

        return $user->id !== $model->id;
    }
}
