<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BanUserRequest;
use App\Http\Resources\Api\V1\CommentResource;
use App\Http\Resources\Api\V1\PostResource;
use App\Http\Resources\Api\V1\PublicUserResource;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Services\ModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ModerationController extends Controller
{
    public function __construct(
        private readonly ModerationService $moderationService
    ) {
    }

    public function removePost(Post $post): JsonResponse
    {
        $this->authorize('moderate');

        $removed = $this->moderationService->removePost($this->user(), $post);

        return response()->json([
            'status' => 'success',
            'message' => 'Post removed',
            'data' => new PostResource($removed),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function restorePost(Post $post): JsonResponse
    {
        $this->authorize('moderate');

        $restored = $this->moderationService->restorePost($this->user(), $post);

        return response()->json([
            'status' => 'success',
            'message' => 'Post restored',
            'data' => new PostResource($restored),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function removeComment(Comment $comment): JsonResponse
    {
        $this->authorize('moderate');

        $removed = $this->moderationService->removeComment($this->user(), $comment);

        return response()->json([
            'status' => 'success',
            'message' => 'Comment removed',
            'data' => new CommentResource($removed),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function restoreComment(Comment $comment): JsonResponse
    {
        $this->authorize('moderate');

        $restored = $this->moderationService->restoreComment($this->user(), $comment);

        return response()->json([
            'status' => 'success',
            'message' => 'Comment restored',
            'data' => new CommentResource($restored),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function banUser(BanUserRequest $request, User $user): JsonResponse
    {
        $this->authorize('moderate');
        Gate::authorize('ban', $user);

        $banned = $this->moderationService->banUser(
            $this->user(),
            $user,
            $request->validated('reason'),
            $request->validated('duration_days')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'User banned',
            'data' => new PublicUserResource($banned),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function unbanUser(User $user): JsonResponse
    {
        $this->authorize('moderate');
        Gate::authorize('unban', $user);

        $unbanned = $this->moderationService->unbanUser($this->user(), $user);

        return response()->json([
            'status' => 'success',
            'message' => 'User unbanned',
            'data' => new PublicUserResource($unbanned),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    private function user(): User
    {
        return auth('sanctum')->user();
    }
}
