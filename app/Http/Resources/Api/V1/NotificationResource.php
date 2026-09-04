<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'data' => $this->data,
            'read_at' => $this->read_at?->toIso8601String(),
            'is_read' => $this->is_read,
            'created_at' => $this->created_at?->toIso8601String(),
            'notifiable' => $this->whenLoaded('notifiable', fn () => [
                'id' => $this->notifiable->getKey(),
                'type' => $this->notifiable_type,
            ]),
        ];
    }
}
