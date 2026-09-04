<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSearchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'display_name' => $this->display_name,
            'avatar' => $this->avatar,
            'karma_score' => $this->karma_score,
            'post_count' => $this->post_count,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
