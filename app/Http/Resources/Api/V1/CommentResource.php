<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'vote_score' => $this->vote_score,
            'upvote_count' => $this->upvote_count,
            'downvote_count' => $this->downvote_count,
            'reply_count' => $this->reply_count,
            'depth' => $this->depth,
            'is_deleted' => $this->is_deleted,
            'is_edited' => $this->edited_at !== null,
            'created_at' => $this->created_at?->toIso8601String(),
            'edited_at' => $this->edited_at?->toIso8601String(),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'username' => $this->user->username,
                'avatar' => $this->user->avatar,
                'display_name' => $this->user->display_name,
            ]),
        ];
    }
}
