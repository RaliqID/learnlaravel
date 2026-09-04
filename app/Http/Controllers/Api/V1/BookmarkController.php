<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BookmarkListRequest;
use App\Http\Requests\Api\V1\BookmarkRequest;
use App\Http\Resources\Api\V1\BookmarkResource;
use App\Models\Bookmark;
use App\Models\Post;
use App\Services\BookmarkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class BookmarkController extends Controller
{
    public function __construct(
        private readonly BookmarkService $bookmarkService
    ) {
    }

    public function index(BookmarkListRequest $request): JsonResponse
    {
        $paginator = $this->bookmarkService->listForUser(
            $this->user(),
            $request->collection(),
            $request->page(),
            $request->perPage()
        );

        return response()->json([
            'status' => 'success',
            'data' => BookmarkResource::collection($paginator->items()),
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

    public function store(BookmarkRequest $request, Post $post): JsonResponse
    {
        Gate::authorize('create', $post);

        $bookmark = $this->bookmarkService->bookmark(
            $this->user(),
            $post,
            $request->validated()['collection_name'] ?? null,
            $request->validated()['notes'] ?? null
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Post bookmarked successfully',
            'data' => new BookmarkResource($bookmark),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ], 201);
    }

    public function destroy(Post $post): JsonResponse
    {
        $removed = $this->bookmarkService->unbookmark($this->user(), $post);

        return response()->json([
            'status' => 'success',
            'message' => $removed ? 'Bookmark removed' : 'Post is not bookmarked',
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function removeById(Bookmark $bookmark): JsonResponse
    {
        Gate::authorize('delete', $bookmark);

        $this->bookmarkService->removeById($this->user(), $bookmark);

        return response()->json([
            'status' => 'success',
            'message' => 'Bookmark removed',
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function collections(): JsonResponse
    {
        $collections = $this->bookmarkService->collections($this->user());

        return response()->json([
            'status' => 'success',
            'data' => $collections->map(fn ($item) => [
                'name' => $item->collection_name,
                'total' => (int) $item->total,
            ])->values(),
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
