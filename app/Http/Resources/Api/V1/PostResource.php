<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'post_type' => $this->post_type,
            'image_url' => $this->image_url,
            'thumbnail_url' => $this->thumbnail_url,
            'vote_score' => $this->vote_score,
            'comment_count' => $this->comment_count,
            'view_count' => $this->view_count,
            'is_pinned' => $this->is_pinned,
            'is_featured' => $this->is_featured,
            'source_name' => $this->source_name,
            'source_url' => $this->source_url,
            'published_at' => $this->published_at?->toIso8601String(),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'username' => $this->user->username,
                'display_name' => $this->user->display_name,
                'avatar' => $this->user->avatar,
            ]),
            'topic' => $this->whenLoaded('topic', fn () => [
                'id' => $this->topic->id,
                'name' => $this->topic->name,
                'slug' => $this->topic->slug,
            ]),
        ];
    }
}
