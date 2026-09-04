<?php

use App\Http\Middleware\EnsureEmailIsVerified;
use App\Http\Middleware\EnsureUserIsNotBanned;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'verified' => EnsureEmailIsVerified::class,
            'not.banned' => EnsureUserIsNotBanned::class,
        ]);

        $middleware->api(append: [
            EnsureUserIsNotBanned::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, Throwable $e): bool => $request->is('api/*') || $request->expectsJson()
        );

        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if (!$request->is('api/*') && !$request->expectsJson()) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Method not allowed',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 405);
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if (!$request->is('api/*') && !$request->expectsJson()) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Resource not found',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 404);
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, Request $request) {
            if (!$request->is('api/*') && !$request->expectsJson()) {
                return null;
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Too many requests. Please try again later.',
                'meta' => [
                    'retry_after' => $e->getHeaders()['Retry-After'] ?? null,
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 429);
        });
    })->create();
