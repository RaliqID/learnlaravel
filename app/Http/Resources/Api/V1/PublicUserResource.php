<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'display_name' => $this->display_name,
            'avatar' => $this->avatar,
            'bio' => $this->bio,
            'website' => $this->website,
            'location' => $this->location,
            'email' => $this->when($this->show_email, $this->email),
            'karma_score' => $this->karma_score,
            'post_count' => $this->posts_count ?? $this->post_count,
            'comment_count' => $this->comments_count ?? $this->comment_count,
            'followers_count' => $this->followers_count ?? 0,
            'following_count' => $this->following_count ?? 0,
            'likes_count' => $this->likes_count ?? 0,
            'is_verified' => $this->email_verified_at !== null,
            'is_following' => $this->is_following ?? false,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
