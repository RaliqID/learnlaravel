<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCommentRequest;
use App\Http\Requests\Api\V1\StoreReplyRequest;
use App\Http\Requests\Api\V1\UpdateCommentRequest;
use App\Http\Resources\Api\V1\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use App\Services\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    public function __construct(
        private readonly CommentService $commentService
    ) {
    }

    public function index(Post $post): JsonResponse
    {
        $perPage = min(max((int) request('per_page', 25), 1), 50);
        $page = max((int) request('page', 1), 1);
        $sort = in_array(request('sort'), ['new', 'old', 'top'], true) ? request('sort') : 'new';

        $comments = $post->comments()
            ->with('user:id,username,display_name,avatar')
            ->whereNull('parent_id')
            ->where('is_deleted', false)
            ->orderBy('vote_score', 'desc');

        if ($sort === 'new') {
            $comments->orderByDesc('created_at');
        } elseif ($sort === 'old') {
            $comments->orderBy('created_at');
        }

        $paginator = $comments->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'status' => 'success',
            'data' => CommentResource::collection($paginator->items()),
            'meta' => [
                'pagination' => [
                    'total' => $paginator->total(),
                    'count' => $paginator->count(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                ],
                'sort' => $sort,
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function store(StoreCommentRequest $request, Post $post): JsonResponse
    {
        Gate::authorize('create', Comment::class);

        $comment = $this->commentService->createComment($post, [
            'user_id' => $this->user()->id,
            'parent_id' => null,
            'content' => $request->validated()['content'],
            'depth' => 0,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Comment posted successfully',
            'data' => new CommentResource($comment),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ], 201);
    }

    public function reply(Comment $comment, StoreReplyRequest $request): JsonResponse
    {
        Gate::authorize('create', Comment::class);

        if ($comment->is_deleted) {
            abort(404, 'Comment not found');
        }

        if ($comment->post->status !== 'published') {
            abort(422, 'Cannot reply to unpublished post');
        }

        $newReply = $this->commentService->createComment($comment->post, [
            'user_id' => $this->user()->id,
            'parent_id' => $comment->id,
            'content' => $request->validated()['content'],
            'depth' => $comment->depth + 1,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Reply posted successfully',
            'data' => new CommentResource($newReply),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ], 201);
    }

    public function show(Post $post, Comment $comment): JsonResponse
    {
        Gate::authorize('view', $comment);

        if ($comment->post_id !== $post->id || $comment->is_deleted) {
            abort(404, 'Comment not found');
        }

        return response()->json([
            'status' => 'success',
            'data' => new CommentResource($comment),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function update(UpdateCommentRequest $request, Comment $comment): JsonResponse
    {
        Gate::authorize('update', $comment);

        $comment = $this->commentService->updateComment($comment, $request->validated(), $this->user());

        return response()->json([
            'status' => 'success',
            'message' => 'Comment updated successfully',
            'data' => new CommentResource($comment),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function destroy(Comment $comment): JsonResponse
    {
        Gate::authorize('delete', $comment);

        $this->commentService->deleteComment($comment, $this->user());

        return response()->json([
            'status' => 'success',
            'message' => 'Comment deleted successfully',
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
