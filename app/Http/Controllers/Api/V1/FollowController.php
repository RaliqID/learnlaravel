<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProfileListRequest;
use App\Http\Resources\Api\V1\PublicUserResource;
use App\Models\User;
use App\Services\FollowService;
use Illuminate\Http\JsonResponse;

class FollowController extends Controller
{
    public function __construct(
        private readonly FollowService $followService
    ) {
    }

    public function store(User $user): JsonResponse
    {
        $result = $this->followService->follow($this->user(), $user);

        return response()->json([
            'status' => 'success',
            'message' => $result['created']
                ? 'You are now following this user'
                : 'You are already following this user',
            'data' => [
                'following' => $result['following'],
                'created' => $result['created'],
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function destroy(User $user): JsonResponse
    {
        $result = $this->followService->unfollow($this->user(), $user);

        return response()->json([
            'status' => 'success',
            'message' => $result['removed']
                ? 'You have unfollowed this user'
                : 'You were not following this user',
            'data' => [
                'following' => $result['following'],
                'removed' => $result['removed'],
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function followers(User $user, ProfileListRequest $request): JsonResponse
    {
        $paginator = $this->followService->followers($user, $this->optionalUser(), $request->page(), $request->perPage());

        return response()->json([
            'status' => 'success',
            'data' => PublicUserResource::collection($paginator->items()),
            'meta' => [
                'pagination' => $this->paginationMeta($paginator),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function following(User $user, ProfileListRequest $request): JsonResponse
    {
        $paginator = $this->followService->following($user, $this->optionalUser(), $request->page(), $request->perPage());

        return response()->json([
            'status' => 'success',
            'data' => PublicUserResource::collection($paginator->items()),
            'meta' => [
                'pagination' => $this->paginationMeta($paginator),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    private function user(): User
    {
        return auth('sanctum')->user();
    }

    private function optionalUser(): ?User
    {
        return auth('sanctum')->user();
    }

    private function paginationMeta(\Illuminate\Pagination\LengthAwarePaginator $paginator): array
    {
        return [
            'total' => $paginator->total(),
            'count' => $paginator->count(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }
}
