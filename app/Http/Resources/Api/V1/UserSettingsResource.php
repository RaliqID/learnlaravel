<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserSettingsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'show_email' => (bool) $this->show_email,
            'email_notifications' => (bool) $this->email_notifications,
        ];
    }
}
