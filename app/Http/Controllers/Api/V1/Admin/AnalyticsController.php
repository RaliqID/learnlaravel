<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\AnalyticsRequest;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;

class AnalyticsController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analyticsService
    ) {
    }

    public function overview(AnalyticsRequest $request): JsonResponse
    {
        return $this->dataResponse($this->analyticsService->overview($request->period()));
    }

    public function users(AnalyticsRequest $request): JsonResponse
    {
        return $this->dataResponse($this->analyticsService->userActivity($request->period(), $request->granularity()));
    }

    public function content(AnalyticsRequest $request): JsonResponse
    {
        return $this->dataResponse($this->analyticsService->contentPerformance($request->period(), $request->limit()));
    }

    public function moderation(AnalyticsRequest $request): JsonResponse
    {
        return $this->dataResponse($this->analyticsService->moderation($request->period()));
    }

    private function dataResponse(array $data): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $data,
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}
