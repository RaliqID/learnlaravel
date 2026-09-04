<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'display_name' => $this->display_name,
            'avatar' => $this->avatar,
            'role' => $this->role,
            'karma_score' => $this->karma_score,
            'post_count' => $this->post_count,
            'comment_count' => $this->comment_count,
            'followers_count' => $this->followers_count ?? 0,
            'email_verified' => $this->email_verified_at !== null,
            'is_banned' => $this->is_banned,
            'banned_until' => $this->banned_until?->toIso8601String(),
            'banned_reason' => $this->banned_reason,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
