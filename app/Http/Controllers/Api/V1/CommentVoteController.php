<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class CommentVoteController extends Controller
{
    public function __construct(
        private readonly CommentService $commentService
    ) {
    }

    public function upvote(Comment $comment): JsonResponse
    {
        Gate::authorize('vote', $comment);

        $result = $this->commentService->voteComment($comment, $this->user(), 1);

        return response()->json([
            'status' => 'success',
            'message' => 'Upvoted comment',
            'data' => [
                'comment_id' => $comment->id,
                'vote_score' => $result->vote_score,
                'user_vote' => 1,
            ],
        ]);
    }

    public function downvote(Comment $comment): JsonResponse
    {
        Gate::authorize('vote', $comment);

        $result = $this->commentService->voteComment($comment, $this->user(), -1);

        return response()->json([
            'status' => 'success',
            'message' => 'Downvoted comment',
            'data' => [
                'comment_id' => $comment->id,
                'vote_score' => $result->vote_score,
                'user_vote' => -1,
            ],
        ]);
    }

    public function removeVote(Comment $comment): JsonResponse
    {
        $this->commentService->removeVoteFromComment($comment, $this->user());

        return response()->json([
            'status' => 'success',
            'message' => 'Vote removed',
        ]);
    }

    private function user(): \App\Models\User
    {
        return auth('sanctum')->user();
    }
}
