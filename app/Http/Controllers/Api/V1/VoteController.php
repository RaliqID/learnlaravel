<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Services\VoteService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class VoteController extends Controller
{
    public function __construct(
        private readonly VoteService $voteService
    ) {
    }

    public function upvote(Post $post): JsonResponse
    {
        $post = $this->guardModelBinding();
        $this->checkBanned();

        $result = $this->voteService->upvote($this->user(), $post);

        return $this->voteResponse($result, 'Vote recorded');
    }

    public function downvote(Post $post): JsonResponse
    {
        $post = $this->guardModelBinding();
        $this->checkBanned();

        $result = $this->voteService->downvote($this->user(), $post);

        return $this->voteResponse($result, 'Vote recorded');
    }

    public function removeVote(Post $post): JsonResponse
    {
        $post = $this->guardModelBinding();
        $this->checkBanned();

        $result = $this->voteService->removeVote($this->user(), $post);

        return $this->voteResponse($result, 'Vote removed');
    }

    private function guardModelBinding(): Post
    {
        $post = request()->route('post');

        if (!$post instanceof Post) {
            throw new NotFoundHttpException('Post not found');
        }

        if ($post->trashed()) {
            throw new NotFoundHttpException('Post not found');
        }

        if ($post->status !== 'published') {
            throw new NotFoundHttpException('Post not found');
        }

        if (!$post->is_approved) {
            throw new UnprocessableEntityHttpException('This post is pending approval.');
        }

        if ($post->is_locked) {
            throw new UnprocessableEntityHttpException('This post is locked and cannot be voted on.');
        }

        return $post;
    }

    private function checkBanned(): void
    {
        if ($this->user()->is_banned) {
            throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException('Your account is banned');
        }
    }

    private function voteResponse(array $result, string $message): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $result,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    private function user(): \App\Models\User
    {
        return auth('sanctum')->user();
    }
}
