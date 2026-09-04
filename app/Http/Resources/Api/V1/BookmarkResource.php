<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookmarkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'collection_name' => $this->collection_name,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'post' => $this->whenLoaded('post', fn () => new PostResource($this->post)),
        ];
    }
}
