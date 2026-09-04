<?php

namespace App\Repositories;

use App\Models\Notification;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function listForUser(int $userId, bool $unreadOnly, int $page, int $perPage): LengthAwarePaginator
    {
        $query = Notification::query()
            ->forUser($userId)
            ->when($unreadOnly, fn ($q) => $q->unread())
            ->orderByDesc('created_at');

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function unreadCount(int $userId): int
    {
        return Notification::query()
            ->forUser($userId)
            ->unread()
            ->count();
    }

    public function findForUser(int $userId, string $id): ?Notification
    {
        return Notification::query()
            ->forUser($userId)
            ->find($id);
    }

    public function create(array $data): Notification
    {
        return Notification::create($data);
    }

    public function markAsRead(Notification $notification): void
    {
        $notification->markAsRead();
    }

    public function markAllAsRead(int $userId): int
    {
        return Notification::query()
            ->forUser($userId)
            ->unread()
            ->update(['read_at' => now()]);
    }

    public function delete(Notification $notification): bool
    {
        return (bool) $notification->delete();
    }
}
