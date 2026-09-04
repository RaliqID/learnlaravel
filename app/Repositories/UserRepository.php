<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository implements UserRepositoryInterface
{
    private const LIST_COLUMNS = [
        'users.id', 'users.username', 'users.display_name', 'users.avatar', 'users.role', 'users.is_banned', 'users.banned_until', 'users.banned_reason',
        'users.karma_score', 'users.post_count', 'users.comment_count', 'users.email_verified_at', 'users.created_at',
    ];

    public function all(int $page = 1, int $perPage = 20, ?string $role = null, ?string $search = null, ?bool $banned = null): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = User::query()
            ->select(self::LIST_COLUMNS)
            ->leftJoin('follows', 'follows.following_id', '=', 'users.id')
            ->groupBy('users.id')
            ->selectRaw('COUNT(DISTINCT follows.id) as followers_count')
            ->orderByDesc('users.created_at');

        if (!empty($role)) {
            $query->where('users.role', $role);
        }

        if ($search !== null && $search !== '') {
            $term = '%'.mb_strtolower(trim($search)).'%';
            $query->where(function ($q) use ($term) {
                $q->whereRaw('LOWER(username) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(display_name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(email) LIKE ?', [$term]);
            });
        }

        if ($banned === true || $banned === 1 || $banned === '1' || $banned === 'true') {
            $query->where('is_banned', true)
                ->where(function ($q) {
                    $q->whereNull('banned_until')->orWhere('banned_until', '>', now());
                });
        }

        return $query->paginate($perPage, self::LIST_COLUMNS, 'page', $page);
    }

    public function findForAdmin(string $username): ?User
    {
        return User::query()
            ->where('username', $username)
            ->withCount([
                'posts' => fn ($q) => $q->published()->approved()->visible(),
                'comments' => fn ($q) => $q->where('is_deleted', false),
            ])
            ->leftJoin('follows', 'follows.following_id', '=', 'users.id')
            ->groupBy('users.id')
            ->selectRaw('COUNT(DISTINCT follows.id) as followers_count')
            ->first();
    }
}
