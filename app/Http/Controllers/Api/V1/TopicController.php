<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreTopicRequest;
use App\Http\Requests\Api\V1\TopicListRequest;
use App\Http\Requests\Api\V1\UpdateTopicRequest;
use App\Http\Resources\Api\V1\TopicResource;
use App\Models\Topic;
use App\Services\TopicService;
use Illuminate\Http\JsonResponse;

class TopicController extends Controller
{
    public function __construct(
        private readonly TopicService $topicService
    ) {
    }

    public function index(TopicListRequest $request): JsonResponse
    {
        $paginator = $this->topicService->list(
            $request->filters(),
            $request->page(),
            $request->perPage(),
            $request->user()?->id
        );

        return response()->json([
            'status' => 'success',
            'data' => [
                'topics' => TopicResource::collection($paginator->items()),
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

    public function show(Topic $topic): JsonResponse
    {
        $detail = $this->topicService->show($topic, auth('sanctum')->id());

        return response()->json([
            'status' => 'success',
            'data' => [
                'topic' => new TopicResource($detail),
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function store(StoreTopicRequest $request): JsonResponse
    {
        $topic = $this->topicService->create($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Topic created successfully',
            'data' => [
                'topic' => new TopicResource($topic),
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ], 201);
    }

    public function update(UpdateTopicRequest $request, Topic $topic): JsonResponse
    {
        $updated = $this->topicService->update($topic, $request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Topic updated successfully',
            'data' => [
                'topic' => new TopicResource($updated),
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function destroy(Topic $topic): JsonResponse
    {
        $this->authorize('delete', $topic);

        $this->topicService->delete($topic);

        return response()->json([
            'status' => 'success',
            'message' => 'Topic deleted successfully',
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function subscribe(Topic $topic): JsonResponse
    {
        $result = $this->topicService->subscribe($topic, auth('sanctum')->id());
        $detail = $this->topicService->show($result['topic'], auth('sanctum')->id());

        return response()->json([
            'status' => 'success',
            'message' => $result['subscribed'] ? 'Subscribed to topic' : 'Already subscribed to topic',
            'data' => [
                'topic' => new TopicResource($detail),
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function unsubscribe(Topic $topic): JsonResponse
    {
        $result = $this->topicService->unsubscribe($topic, auth('sanctum')->id());
        $detail = $this->topicService->show($result['topic'], auth('sanctum')->id());

        return response()->json([
            'status' => 'success',
            'message' => $result['unsubscribed'] ? 'Unsubscribed from topic' : 'You are not subscribed to this topic',
            'data' => [
                'topic' => new TopicResource($detail),
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}
