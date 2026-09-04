<?php

namespace App\Policies;

use App\Models\User;

class ModerationPolicy
{
    public function moderate(User $user): bool
    {
        return $user->role === 'admin' || $user->role === 'moderator';
    }
}
