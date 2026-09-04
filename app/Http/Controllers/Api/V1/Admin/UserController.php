<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdateUserRoleRequest;
use App\Http\Requests\Api\V1\Admin\UserListRequest;
use App\Http\Resources\Api\V1\Admin\UserResource;
use App\Models\User;
use App\Repositories\UserRepositoryInterface;
use App\Services\AdminService;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function __construct(
        private readonly AdminService $adminService,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function index(UserListRequest $request): JsonResponse
    {
        $paginator = $this->adminService->listUsers(
            $request->page(),
            $request->perPage(),
            $request->role(),
            $request->search(),
            $request->banned()
        );

        return response()->json([
            'status' => 'success',
            'data' => UserResource::collection($paginator->items()),
            'meta' => [
                'pagination' => [
                    'total' => $paginator->total(),
                    'count' => $paginator->count(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                ],
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function show(User $user): JsonResponse
    {
        $adminUser = $this->userRepository->findForAdmin($user->username);

        if (!$adminUser) {
            return response()->json(['status' => 'error', 'message' => 'User not found', 'meta' => ['timestamp' => now()->toIso8601String()]], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => new UserResource($adminUser),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): JsonResponse
    {
        $this->authorize('updateRole', $user);

        $user->forceFill(['role' => $request->validated('role')])->save();

        return response()->json([
            'status' => 'success',
            'message' => 'User role updated',
            'data' => new UserResource($user->fresh()),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}
