<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function create(User $user): bool
    {
        return !$user->is_banned;
    }

    public function viewAny(User $user): bool
    {
        return $user->role === 'admin' || $user->role === 'moderator';
    }

    public function view(User $user, Report $report): bool
    {
        return $user->role === 'admin' || $user->role === 'moderator';
    }

    public function update(User $user, Report $report): bool
    {
        return $user->role === 'admin' || $user->role === 'moderator';
    }
}
