<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserSettingsService
{
    public function updateSettings(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $fillable = [];

            if (array_key_exists('show_email', $data)) {
                $fillable['show_email'] = $this->toBoolean($data['show_email']);
            }

            if (array_key_exists('email_notifications', $data)) {
                $fillable['email_notifications'] = $this->toBoolean($data['email_notifications']);
            }

            if (!empty($fillable)) {
                $user->update($fillable);
            }

            return $user->fresh();
        });
    }

    public function getSettings(User $user): User
    {
        return $user->load([])->setAttribute('settings_loaded', true);
    }

    private function toBoolean($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}
