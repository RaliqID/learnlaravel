<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReportListRequest;
use App\Http\Requests\Api\V1\ResolveReportRequest;
use App\Http\Requests\Api\V1\StoreReportRequest;
use App\Http\Resources\Api\V1\ReportResource;
use App\Models\Report;
use App\Services\ModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ReportController extends Controller
{
    public function __construct(
        private readonly ModerationService $moderationService
    ) {
    }

    public function store(StoreReportRequest $request): JsonResponse
    {
        $report = $this->moderationService->submitReport($request->user(), $request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Report submitted successfully',
            'data' => new ReportResource($report),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ], 201);
    }

    public function index(ReportListRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', Report::class);

        $paginator = $this->moderationService->queue($request->filters(), $request->page(), $request->perPage());

        return response()->json([
            'status' => 'success',
            'data' => ReportResource::collection($paginator->items()),
            'meta' => [
                'pagination' => $this->paginationMeta($paginator),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function show(Report $report): JsonResponse
    {
        Gate::authorize('view', $report);

        $detail = $this->moderationService->reportDetail($report);

        return response()->json([
            'status' => 'success',
            'data' => new ReportResource($detail),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function resolve(ResolveReportRequest $request, Report $report): JsonResponse
    {
        Gate::authorize('update', $report);

        $resolved = $this->moderationService->resolveReport(
            $request->user(),
            $report,
            $request->validated('status'),
            $request->validated('resolution_note')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Report resolved',
            'data' => new ReportResource($resolved),
            'meta' => [
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
