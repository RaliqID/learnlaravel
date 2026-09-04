<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateUserSettingsRequest;
use App\Http\Resources\Api\V1\UserSettingsResource;
use App\Services\UserSettingsService;
use Illuminate\Http\JsonResponse;

class UserSettingsController extends Controller
{
    public function __construct(
        private readonly UserSettingsService $service
    ) {
    }

    public function show(): JsonResponse
    {
        $user = $this->user();

        return response()->json([
            'status' => 'success',
            'data' => new UserSettingsResource($user),
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function update(UpdateUserSettingsRequest $request): JsonResponse
    {
        $user = $this->user();
        $updated = $this->service->updateSettings($user, $request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Settings updated successfully',
            'data' => new UserSettingsResource($updated),
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
