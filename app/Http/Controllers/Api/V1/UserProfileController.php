<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ProfileListRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Requests\Api\V1\UploadAvatarRequest;
use App\Http\Resources\Api\V1\CommentResource;
use App\Http\Resources\Api\V1\PostResource;
use App\Http\Resources\Api\V1\PublicUserResource;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use App\Services\ImageService;
use App\Services\UserProfileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

class UserProfileController extends Controller
{
    public function __construct(
        private readonly UserProfileService $profileService,
        private readonly ImageService $imageService
    ) {
    }

    public function show(User $user): JsonResponse
    {
        $profile = $this->profileService->publicProfile($user, auth('sanctum')->id());

        return response()->json([
            'status' => 'success',
            'data' => new PublicUserResource($profile),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function update(UpdateProfileRequest $request, User $user): JsonResponse
    {
        Gate::authorize('update', $user);

        $updated = $this->profileService->updateProfile($user, $request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully',
            'data' => new UserResource($updated),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function uploadAvatar(UploadAvatarRequest $request, User $user): JsonResponse
    {
        Gate::authorize('update', $user);

        $uploaded = $this->imageService->upload($request->file('avatar'), 'avatars');

        $user->update(['avatar' => $uploaded['image_url']]);

        return response()->json([
            'status' => 'success',
            'message' => 'Avatar uploaded successfully',
            'data' => new UserResource($user->fresh()),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function deleteAvatar(User $user): JsonResponse
    {
        Gate::authorize('update', $user);

        $oldAvatar = $user->avatar;

        $user->update(['avatar' => null]);

        if ($oldAvatar) {
            $this->imageService->deleteUrl($oldAvatar);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Avatar removed successfully',
            'data' => new UserResource($user->fresh()),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function posts(User $user, ProfileListRequest $request): JsonResponse
    {
        $paginator = $this->profileService->userPosts($user, $request->page(), $request->perPage());

        return response()->json([
            'status' => 'success',
            'data' => PostResource::collection($paginator->items()),
            'meta' => [
                'pagination' => $this->paginationMeta($paginator),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function comments(User $user, ProfileListRequest $request): JsonResponse
    {
        $paginator = $this->profileService->userComments($user, $request->page(), $request->perPage());

        return response()->json([
            'status' => 'success',
            'data' => CommentResource::collection($paginator->items()),
            'meta' => [
                'pagination' => $this->paginationMeta($paginator),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
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

    public function updatePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Password updated successfully',
        ]);
    }
}
