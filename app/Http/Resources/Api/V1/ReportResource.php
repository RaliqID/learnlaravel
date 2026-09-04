<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reportable_type' => $this->reportable_type,
            'reportable_id' => $this->reportable_id,
            'reason' => $this->reason,
            'description' => $this->description,
            'status' => $this->status,
            'resolution_note' => $this->resolution_note,
            'resolved_at' => $this->resolved_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'reporter' => $this->whenLoaded('reporter', fn () => [
                'id' => $this->reporter->id,
                'username' => $this->reporter->username,
                'display_name' => $this->reporter->display_name,
                'avatar' => $this->reporter->avatar,
            ]),
            'resolved_by' => $this->whenLoaded('resolvedBy', fn () => [
                'id' => $this->resolvedBy->id,
                'username' => $this->resolvedBy->username,
                'display_name' => $this->resolvedBy->display_name,
            ]),
            'reportable' => $this->whenLoaded('reportable', fn () => $this->reportableSummary()),
        ];
    }

    private function reportableSummary(): array
    {
        $target = $this->reportable;

        if ($this->reportable_type === \App\Models\Post::class) {
            return [
                'type' => 'post',
                'id' => $target->id,
                'title' => $target->title,
                'slug' => $target->slug,
                'status' => $target->status,
                'is_approved' => $target->is_approved,
                'author_username' => $target->user?->username,
            ];
        }

        return [
            'type' => 'comment',
            'id' => $target->id,
            'content' => $target->content,
            'is_deleted' => $target->is_deleted,
            'post_id' => $target->post_id,
        ];
    }
}
