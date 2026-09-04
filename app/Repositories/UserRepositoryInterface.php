<?php

namespace App\Repositories;

use App\Models\User;

interface UserRepositoryInterface
{
    public function all(int $page = 1, int $perPage = 20, ?string $role = null, ?string $search = null, ?bool $banned = null): \Illuminate\Pagination\LengthAwarePaginator;

    public function findForAdmin(string $username): ?User;
}
