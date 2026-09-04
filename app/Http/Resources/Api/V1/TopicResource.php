<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TopicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'icon' => $this->icon,
            'banner' => $this->banner,
            'subscriber_count' => $this->subscriber_count,
            'post_count' => $this->post_count,
            'is_active' => $this->is_active,
            'is_private' => $this->is_private,
            'requires_approval' => $this->requires_approval,
            'rules' => $this->rules,
            'is_subscribed' => (bool) ($this->is_subscribed ?? false),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
