<?php

namespace App\Providers;

use App\Repositories\BookmarkRepository;
use App\Repositories\BookmarkRepositoryInterface;
use App\Repositories\CommentRepository;
use App\Repositories\CommentRepositoryInterface;
use App\Repositories\FollowRepository;
use App\Repositories\FollowRepositoryInterface;
use App\Repositories\NotificationRepository;
use App\Repositories\NotificationRepositoryInterface;
use App\Repositories\PostRepository;
use App\Repositories\PostRepositoryInterface;
use App\Repositories\ReportRepository;
use App\Repositories\ReportRepositoryInterface;
use App\Repositories\TopicRepository;
use App\Repositories\TopicRepositoryInterface;
use App\Repositories\UserRepository;
use App\Repositories\UserRepositoryInterface;
use App\Repositories\VoteRepository;
use App\Repositories\VoteRepositoryInterface;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate as AppGate;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PostRepositoryInterface::class, PostRepository::class);
        $this->app->bind(TopicRepositoryInterface::class, TopicRepository::class);
        $this->app->bind(VoteRepositoryInterface::class, VoteRepository::class);
        $this->app->bind(CommentRepositoryInterface::class, CommentRepository::class);
        $this->app->bind(BookmarkRepositoryInterface::class, BookmarkRepository::class);
        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);
        $this->app->bind(FollowRepositoryInterface::class, FollowRepository::class);
        $this->app->bind(ReportRepositoryInterface::class, ReportRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
    }

    public function boot(): void
    {
        AppGate::define('moderate', [\App\Policies\ModerationPolicy::class, 'moderate']);
        AppGate::define('admin-area', fn ($user) => $user->role === 'admin');

        RateLimiter::for('feed', function (Request $request) {
            $key = $request->user()?->id ?: $request->ip();

            return Limit::perMinute($request->user() ? 60 : 30)
                ->by($key);
        });
    }
}
