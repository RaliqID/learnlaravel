<?php

namespace App\Repositories;

use App\Models\Notification;
use Illuminate\Pagination\LengthAwarePaginator;

interface NotificationRepositoryInterface
{
    public function listForUser(int $userId, bool $unreadOnly, int $page, int $perPage): LengthAwarePaginator;

    public function unreadCount(int $userId): int;

    public function findForUser(int $userId, string $id): ?Notification;

    public function create(array $data): Notification;

    public function markAsRead(Notification $notification): void;

    public function markAllAsRead(int $userId): int;

    public function delete(Notification $notification): bool;
}
