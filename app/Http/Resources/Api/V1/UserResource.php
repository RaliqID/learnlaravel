<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'display_name' => $this->display_name,
            'bio' => $this->bio,
            'avatar' => $this->avatar,
            'website' => $this->website,
            'location' => $this->location,
            'karma_score' => $this->karma_score,
            'post_count' => $this->post_count,
            'comment_count' => $this->comment_count,
            'followers_count' => $this->followers_count ?? 0,
            'following_count' => $this->following_count ?? 0,
            'likes_count' => $this->likes_count ?? 0,
            'is_following' => $this->is_following ?? false,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
