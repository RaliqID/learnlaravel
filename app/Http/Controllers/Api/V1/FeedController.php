<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FeedRequest;
use App\Http\Resources\Api\V1\PostResource;
use App\Http\Resources\Api\V1\TopicResource;
use App\Services\FeedService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function __construct(
        private readonly FeedService $feedService
    ) {
    }

    public function index(FeedRequest $request): JsonResponse
    {
        $paginator = $this->feedService->getFeed(
            $request->filters(),
            $request->page(),
            $request->perPage()
        );

        return response()->json([
            'status' => 'success',
            'data' => [
                'posts' => PostResource::collection($paginator->items()),
            ],
            'meta' => [
                'pagination' => [
                    'total' => $paginator->total(),
                    'count' => $paginator->count(),
                    'per_page' => $paginator->perPage(),
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'total_pages' => $paginator->lastPage(),
                    'links' => [
                        'first' => $paginator->url(1),
                        'last' => $paginator->url($paginator->lastPage()),
                        'prev' => $paginator->previousPageUrl(),
                        'next' => $paginator->nextPageUrl(),
                    ],
                ],
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function hero(): JsonResponse
    {
        $hero = $this->feedService->getHero();

        return response()->json([
            'status' => 'success',
            'data' => [
                'headline' => $hero['headline'] ? new PostResource($hero['headline']) : null,
                'secondary' => PostResource::collection($hero['secondary']),
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function trending(FeedRequest $request): JsonResponse
    {
        $trending = $this->feedService->getTrending($request->timeRange());

        return response()->json([
            'status' => 'success',
            'data' => [
                'trending' => PostResource::collection($trending),
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function recommended(): JsonResponse
    {
        $recommended = $this->feedService->getRecommended();

        return response()->json([
            'status' => 'success',
            'data' => [
                'posts' => PostResource::collection($recommended),
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function categories(): JsonResponse
    {
        $categories = $this->feedService->getCategories();

        return response()->json([
            'status' => 'success',
            'data' => [
                'categories' => TopicResource::collection($categories),
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function editorsPicks(): JsonResponse
    {
        $picks = $this->feedService->getEditorsPicks();

        return response()->json([
            'status' => 'success',
            'data' => [
                'posts' => PostResource::collection($picks),
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function sections(): JsonResponse
    {
        $sections = $this->feedService->getSections();

        return response()->json([
            'status' => 'success',
            'data' => [
                'sections' => array_map(function ($section) {
                    return [
                        'topic' => $section['topic'],
                        'posts' => PostResource::collection($section['posts'])->resolve(),
                    ];
                }, $sections),
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function categoryDistribution(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 7);
        if (!in_array($days, [1, 7, 30, 60])) {
            $days = 7;
        }
        $data = $this->feedService->getCategoryDistribution($days);
        return response()->json([
            'status' => 'success',
            'data' => $data,
            'meta' => [
                'days' => $days,
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function activityChart(): JsonResponse
    {
        $chart = $this->feedService->getActivityChart();

        return response()->json([
            'status' => 'success',
            'data' => $chart,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}
