<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\FeedController;
use App\Http\Controllers\Api\V1\PostController;
use App\Http\Controllers\Api\V1\TopicController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Auth Routes...
    Route::prefix('auth')->group(function () {
        Route::middleware('throttle:10,1')->group(function () {
            Route::post('/register', [AuthController::class, 'register']);
            Route::post('/login', [AuthController::class, 'login']);
            Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
            Route::get('/google', [AuthController::class, 'redirectToGoogle']);
            Route::get('/google/callback', [AuthController::class, 'handleGoogleCallback']);
        });
        
        Route::middleware('throttle:5,1')->group(function () {
            Route::post('/reset-password', [AuthController::class, 'resetPassword']);
        });
        
        Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::post('/email/resend', [AuthController::class, 'resendVerification']);
        });
        
        Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])
            ->middleware('throttle:10,1')
            ->name('verification.verify');
    });

    // Feed Routes...
    Route::middleware('throttle:feed')->prefix('feed')->group(function () {
        Route::get('/', [FeedController::class, 'index']);
        Route::get('/hero', [FeedController::class, 'hero']);
        Route::get('/trending', [FeedController::class, 'trending']);
        Route::get('/recommended', [FeedController::class, 'recommended']);
        Route::get('/categories', [FeedController::class, 'categories']);
        Route::get('/editors-picks', [FeedController::class, 'editorsPicks']);
        Route::get('/sections', [FeedController::class, 'sections']);
        Route::get('/category-distribution', [FeedController::class, 'categoryDistribution']);
        Route::get('/activity-chart', [FeedController::class, 'activityChart']);
    });

    // Topic Routes...
    Route::middleware('throttle:feed')->prefix('topics')->group(function () {
        Route::get('/', [TopicController::class, 'index']);
        Route::get('/{topic:id}', [TopicController::class, 'show']);
    });

    Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('topics')->group(function () {
        Route::post('/', [TopicController::class, 'store']);
        Route::put('/{topic:id}', [TopicController::class, 'update']);
        Route::delete('/{topic:id}', [TopicController::class, 'destroy']);
        Route::post('/{topic:id}/subscribe', [TopicController::class, 'subscribe']);
        Route::delete('/{topic:id}/subscribe', [TopicController::class, 'unsubscribe']);
    });

    // Post Routes...
    Route::middleware('throttle:feed')->prefix('posts')->group(function () {
        Route::get('/{post}', [PostController::class, 'show']);
    });

    Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('posts')->group(function () {
        Route::post('/', [PostController::class, 'store']);
        Route::put('/{post}', [PostController::class, 'update']);
        Route::delete('/{post}', [PostController::class, 'destroy']);
    });

    // Voting Routes
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('posts')->group(function () {
        Route::post('/{post}/vote', [\App\Http\Controllers\Api\V1\VoteController::class, 'upvote'])->name('posts.vote.upvote');
        Route::post('/{post}/vote/down', [\App\Http\Controllers\Api\V1\VoteController::class, 'downvote'])->name('posts.vote.downvote');
    });

    Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('posts')->group(function () {
        Route::delete('/{post}/vote', [\App\Http\Controllers\Api\V1\VoteController::class, 'removeVote'])->name('posts.vote.remove');
    });

    // Comment Routes
    Route::middleware('throttle:feed')->prefix('posts')->group(function () {
        Route::get('/{post}/comments', [\App\Http\Controllers\Api\V1\CommentController::class, 'index']);
        Route::get('/{post}/comments/{comment}', [\App\Http\Controllers\Api\V1\CommentController::class, 'show']);
    });

    Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('posts')->group(function () {
        Route::post('/{post}/comments', [\App\Http\Controllers\Api\V1\CommentController::class, 'store']);
    });

    Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('comments')->group(function () {
        Route::post('/{comment}/replies', [\App\Http\Controllers\Api\V1\CommentController::class, 'reply']);
        Route::put('/{comment}', [\App\Http\Controllers\Api\V1\CommentController::class, 'update']);
        Route::delete('/{comment}', [\App\Http\Controllers\Api\V1\CommentController::class, 'destroy']);
    });

    // Comment Voting Routes
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('comments')->group(function () {
        Route::post('/{comment}/vote', [\App\Http\Controllers\Api\V1\CommentVoteController::class, 'upvote'])->name('comments.vote.upvote');
        Route::post('/{comment}/vote/down', [\App\Http\Controllers\Api\V1\CommentVoteController::class, 'downvote'])->name('comments.vote.downvote');
    });

    Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('comments')->group(function () {
        Route::delete('/{comment}/vote', [\App\Http\Controllers\Api\V1\CommentVoteController::class, 'removeVote'])->name('comments.vote.remove');
    });

    // Bookmark Routes
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('posts')->group(function () {
        Route::post('/{post}/bookmark', [\App\Http\Controllers\Api\V1\BookmarkController::class, 'store'])->name('posts.bookmark.store');
        Route::delete('/{post}/bookmark', [\App\Http\Controllers\Api\V1\BookmarkController::class, 'destroy'])->name('posts.bookmark.destroy');
    });

    Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('bookmarks')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\BookmarkController::class, 'index']);
        Route::get('/collections', [\App\Http\Controllers\Api\V1\BookmarkController::class, 'collections']);
        Route::delete('/{bookmark}', [\App\Http\Controllers\Api\V1\BookmarkController::class, 'removeById']);
    });

    // Notification Routes
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('notifications')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\NotificationController::class, 'index']);
        Route::get('/unread-count', [\App\Http\Controllers\Api\V1\NotificationController::class, 'unreadCount']);
        Route::put('/read-all', [\App\Http\Controllers\Api\V1\NotificationController::class, 'readAll']);
        Route::get('/{notification}', [\App\Http\Controllers\Api\V1\NotificationController::class, 'show']);
        Route::put('/{notification}/read', [\App\Http\Controllers\Api\V1\NotificationController::class, 'read']);
        Route::delete('/{notification}', [\App\Http\Controllers\Api\V1\NotificationController::class, 'destroy']);
    });

    // Search Routes
    Route::middleware('throttle:feed')->prefix('search')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\SearchController::class, 'index']);
        Route::get('/posts', [\App\Http\Controllers\Api\V1\SearchController::class, 'posts']);
        Route::get('/topics', [\App\Http\Controllers\Api\V1\SearchController::class, 'topics']);
        Route::get('/users', [\App\Http\Controllers\Api\V1\SearchController::class, 'users']);
    });

    // Report Routes
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('reports')->group(function () {
        Route::post('/', [\App\Http\Controllers\Api\V1\ReportController::class, 'store']);
    });

    // Moderation Routes
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('moderation')->group(function () {
        Route::get('/reports', [\App\Http\Controllers\Api\V1\ReportController::class, 'index']);
        Route::get('/reports/{report}', [\App\Http\Controllers\Api\V1\ReportController::class, 'show']);
        Route::put('/reports/{report}', [\App\Http\Controllers\Api\V1\ReportController::class, 'resolve']);
        Route::post('/posts/{post}/remove', [\App\Http\Controllers\Api\V1\ModerationController::class, 'removePost']);
        Route::post('/posts/{post}/restore', [\App\Http\Controllers\Api\V1\ModerationController::class, 'restorePost'])->withTrashed();
        Route::post('/comments/{comment}/remove', [\App\Http\Controllers\Api\V1\ModerationController::class, 'removeComment']);
        Route::post('/comments/{comment}/restore', [\App\Http\Controllers\Api\V1\ModerationController::class, 'restoreComment'])->withTrashed();
        Route::post('/users/{user:username}/ban', [\App\Http\Controllers\Api\V1\ModerationController::class, 'banUser']);
        Route::post('/users/{user:username}/unban', [\App\Http\Controllers\Api\V1\ModerationController::class, 'unbanUser']);
    });

    // Admin Routes (admin only)
    Route::middleware(['auth:sanctum', 'can:admin-area', 'throttle:60,1'])->prefix('admin')->group(function () {
        Route::get('/dashboard', [\App\Http\Controllers\Api\V1\Admin\DashboardController::class, 'index']);
        Route::get('/users', [\App\Http\Controllers\Api\V1\Admin\UserController::class, 'index']);
        Route::get('/users/{user:username}', [\App\Http\Controllers\Api\V1\Admin\UserController::class, 'show']);
        Route::put('/users/{user:username}/role', [\App\Http\Controllers\Api\V1\Admin\UserController::class, 'updateRole']);
        Route::get('/analytics/overview', [\App\Http\Controllers\Api\V1\Admin\AnalyticsController::class, 'overview']);
        Route::get('/analytics/users', [\App\Http\Controllers\Api\V1\Admin\AnalyticsController::class, 'users']);
        Route::get('/analytics/content', [\App\Http\Controllers\Api\V1\Admin\AnalyticsController::class, 'content']);
        Route::get('/analytics/moderation', [\App\Http\Controllers\Api\V1\Admin\AnalyticsController::class, 'moderation']);
    });

    // User Routes...
    Route::middleware('throttle:feed')->prefix('users')->group(function () {
        Route::get('/{user:username}', [\App\Http\Controllers\Api\V1\UserProfileController::class, 'show']);
        Route::get('/{user:username}/posts', [\App\Http\Controllers\Api\V1\UserProfileController::class, 'posts']);
        Route::get('/{user:username}/comments', [\App\Http\Controllers\Api\V1\UserProfileController::class, 'comments']);
        Route::get('/{user:username}/followers', [\App\Http\Controllers\Api\V1\FollowController::class, 'followers']);
        Route::get('/{user:username}/following', [\App\Http\Controllers\Api\V1\FollowController::class, 'following']);
    });

    Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('users')->group(function () {
        Route::put('/{user:username}', [\App\Http\Controllers\Api\V1\UserProfileController::class, 'update']);
        Route::post('/{user:username}/avatar', [\App\Http\Controllers\Api\V1\UserProfileController::class, 'uploadAvatar']);
        Route::delete('/{user:username}/avatar', [\App\Http\Controllers\Api\V1\UserProfileController::class, 'deleteAvatar']);
        Route::post('/{user:username}/follow', [\App\Http\Controllers\Api\V1\FollowController::class, 'store']);
        Route::delete('/{user:username}/follow', [\App\Http\Controllers\Api\V1\FollowController::class, 'destroy']);
    });

    Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
        Route::get('/user', function (Request $request) {
            return response()->json([
                'status' => 'success',
                'data' => new \App\Http\Resources\Api\V1\UserResource($request->user()),
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ]);
        });
        Route::get('/user/posts', [PostController::class, 'myPosts']);
        Route::put('/user/password', [\App\Http\Controllers\Api\V1\UserProfileController::class, 'updatePassword']);
    });

    // Chat Routes
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('chat')->group(function () {
        Route::get('/conversations', [\App\Http\Controllers\Api\V1\ChatController::class, 'conversations']);
        Route::get('/conversations/{conversation}/messages', [\App\Http\Controllers\Api\V1\ChatController::class, 'messages']);
        Route::post('/send', [\App\Http\Controllers\Api\V1\ChatController::class, 'store']);
        Route::post('/start', [\App\Http\Controllers\Api\V1\ChatController::class, 'startConversation']);
    });

    // User Settings Routes
    Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('user/settings')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\V1\UserSettingsController::class, 'show']);
        Route::put('/', [\App\Http\Controllers\Api\V1\UserSettingsController::class, 'update']);
        Route::patch('/', [\App\Http\Controllers\Api\V1\UserSettingsController::class, 'update']);
    });
});
