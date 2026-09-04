<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsNotBanned
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->user()->is_banned) {
            $bannedUntil = $request->user()->banned_until;
            
            if ($bannedUntil && now()->greaterThan($bannedUntil)) {
                $request->user()->update([
                    'is_banned' => false,
                    'banned_until' => null,
                    'banned_reason' => null,
                ]);
                
                return $next($request);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Your account has been banned',
                'data' => [
                    'banned_until' => $bannedUntil?->toIso8601String(),
                    'banned_reason' => $request->user()->banned_reason,
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 403);
        }

        return $next($request);
    }
}
