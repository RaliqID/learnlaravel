<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SearchRequest;
use App\Http\Resources\Api\V1\PostResource;
use App\Http\Resources\Api\V1\TopicResource;
use App\Http\Resources\Api\V1\UserSearchResource;
use App\Services\SearchService;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function __construct(
        private readonly SearchService $searchService
    ) {
    }

    public function index(SearchRequest $request): JsonResponse
    {
        return $this->searchPostsResponse($request);
    }

    public function posts(SearchRequest $request): JsonResponse
    {
        return $this->searchPostsResponse($request);
    }

    public function topics(SearchRequest $request): JsonResponse
    {
        $paginator = $this->searchService->searchTopics(
            $request->searchTerm(),
            $request->page(),
            $request->perPage()
        );

        return response()->json([
            'status' => 'success',
            'data' => TopicResource::collection($paginator->items()),
            'meta' => [
                'query' => $request->searchTerm(),
                'pagination' => $this->paginationMeta($paginator),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function users(SearchRequest $request): JsonResponse
    {
        $paginator = $this->searchService->searchUsers(
            $request->searchTerm(),
            $request->page(),
            $request->perPage()
        );

        return response()->json([
            'status' => 'success',
            'data' => UserSearchResource::collection($paginator->items()),
            'meta' => [
                'query' => $request->query(),
                'pagination' => $this->paginationMeta($paginator),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    private function searchPostsResponse(SearchRequest $request): JsonResponse
    {
        $paginator = $this->searchService->searchPosts(
            $request->postFilters(),
            $request->page(),
            $request->perPage()
        );

        return response()->json([
            'status' => 'success',
            'data' => PostResource::collection($paginator->items()),
            'meta' => [
                'query' => $request->query(),
                'sort' => $request->sort(),
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
}
