<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PostListRequest;
use App\Http\Requests\Api\V1\StorePostRequest;
use App\Http\Requests\Api\V1\UpdatePostRequest;
use App\Http\Resources\Api\V1\PostDetailResource;
use App\Http\Resources\Api\V1\PostResource;
use App\Models\Post;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    public function __construct(
        private readonly PostService $postService
    ) {
    }

    public function myPosts(PostListRequest $request): JsonResponse
    {
        $status = $request->input('status', 'published');
        
        $paginator = $this->postService->getUserPosts(
            $request->user()->id,
            $status,
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

    public function show($identifier): JsonResponse
    {
        $post = $this->postService->resolveForReading($identifier);

        if (!$post) {
            return response()->json([
                'status' => 'error',
                'message' => 'Post not found',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 404);
        }

        $this->authorize('view', $post);

        $this->postService->recordView($post);
        $post->refresh(['user', 'topic']);

        $navigation = $this->postService->navigation($post);

        return response()->json([
            'status' => 'success',
            'data' => [
                'post' => new PostDetailResource($post),
                'related_posts' => PostResource::collection($this->postService->relatedPosts($post)),
                'navigation' => [
                    'previous' => $navigation['previous'] ? new PostResource($navigation['previous']) : null,
                    'next' => $navigation['next'] ? new PostResource($navigation['next']) : null,
                ],
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = $request->user()->id;

        // Check for duplicate URL
        if (($data['post_type'] ?? '') === 'link' && !empty($data['url'])) {
            $duplicate = $this->postService->findDuplicateUrl($data['url']);
            
            if ($duplicate) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This link has already been posted',
                    'data' => [
                        'duplicate_post_id' => $duplicate->id,
                        'duplicate_post_slug' => $duplicate->slug,
                    ]
                ], 409);
            }
        }

        $image = $request->file('image');
        $post = $this->postService->store($data, $image);

        return response()->json([
            'status' => 'success',
            'message' => $post->status === 'published' ? 'Post published successfully' : 'Draft saved successfully',
            'data' => [
                'post' => new PostDetailResource($post),
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ], 201);
    }

    public function update(UpdatePostRequest $request, Post $post): JsonResponse
    {
        $data = $request->validated();

        // Check for duplicate URL if changing URL
        if (($data['post_type'] ?? $post->post_type) === 'link' && !empty($data['url'])) {
            $duplicate = $this->postService->findDuplicateUrl($data['url'], $post->id);
            
            if ($duplicate) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'This link has already been posted',
                    'data' => [
                        'duplicate_post_id' => $duplicate->id,
                        'duplicate_post_slug' => $duplicate->slug,
                    ]
                ], 409);
            }
        }

        $image = $request->file('image');
        $updated = $this->postService->update($post, $data, $image);

        return response()->json([
            'status' => 'success',
            'message' => 'Post updated successfully',
            'data' => [
                'post' => new PostDetailResource($updated),
            ],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public function destroy(Post $post): JsonResponse
    {
        $this->authorize('delete', $post);

        $this->postService->destroy($post);

        return response()->json([
            'status' => 'success',
            'message' => 'Post deleted successfully',
            'meta' => [
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }
}