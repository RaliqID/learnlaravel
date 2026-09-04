<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'content_html' => $this->content_html,
            'excerpt' => $this->excerpt,
            'url' => $this->url,
            'canonical_url' => $this->canonical_url,
            'share_url' => $this->canonical_url ?: url("/posts/{$this->slug}"),
            'reading_time' => $this->reading_time,
            'post_type' => $this->post_type,
            'image_url' => $this->image_url,
            'thumbnail_url' => $this->thumbnail_url,
            
            // Meta & SEO
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            
            // Status & Flags
            'status' => $this->status,
            'is_pinned' => $this->is_pinned,
            'is_locked' => $this->is_locked,
            'is_nsfw' => $this->is_nsfw,
            'is_approved' => $this->is_approved,
            'is_featured' => $this->is_featured,
            'source_name' => $this->source_name,
            'source_url' => $this->source_url,
            
            // Engagement Stats
            'vote_score' => $this->vote_score,
            'upvote_count' => $this->upvote_count,
            'downvote_count' => $this->downvote_count,
            'comment_count' => $this->comment_count,
            'view_count' => $this->view_count,
            'bookmark_count' => $this->bookmark_count,
            
            // Timestamps
            'published_at' => $this->published_at?->toIso8601String(),
            'edited_at' => $this->edited_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            
            // Relations
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