<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\NotificationListRequest;
use App\Http\Resources\Api\V1\NotificationResource;
use App\Models\Notification;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {
    }

    public function index(NotificationListRequest $request): JsonResponse
    {
        $paginator = $this->notificationService->listForUser(
            $this->user(),
            $request->unreadOnly(),
            $request->page(),
            $request->perPage()
        );

        return response()->json([
            'status' => 'success',
            'data' => NotificationResource::collection($paginator->items()),
            'meta' => [
                'unread_count' => $this->notificationService->unreadCount($this->user()),
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

    public function show(Notification $notification): JsonResponse
    {
        Gate::authorize('view', $notification);

        return response()->json([
            'status' => 'success',
            'data' => new NotificationResource($notification),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function read(Notification $notification): JsonResponse
    {
        Gate::authorize('update', $notification);

        $notification = $this->notificationService->markAsRead($this->user(), $notification);

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read',
            'data' => new NotificationResource($notification),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function readAll(): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead($this->user());

        return response()->json([
            'status' => 'success',
            'message' => 'All notifications marked as read',
            'data' => [
                'marked' => $count,
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function destroy(Notification $notification): JsonResponse
    {
        Gate::authorize('delete', $notification);

        $this->notificationService->delete($this->user(), $notification);

        return response()->json([
            'status' => 'success',
            'message' => 'Notification deleted',
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function unreadCount(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'unread_count' => $this->notificationService->unreadCount($this->user()),
            ],
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
