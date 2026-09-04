<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $user = User::create([
                'username' => $request->username,
                'email' => $request->email,
                'password' => $request->password,
                'display_name' => $request->display_name ?? $request->username,
            ]);

            event(new Registered($user));

            $token = $user->createToken('auth_token')->plainTextToken;

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'User registered successfully. Please verify your email.',
                'data' => [
                    'token' => $token,
                    'user' => new UserResource($user),
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Registration failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Registration failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid credentials',
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], 401);
            }

            if ($user->is_banned) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Your account has been banned',
                    'data' => [
                        'banned_until' => $user->banned_until?->toIso8601String(),
                        'banned_reason' => $user->banned_reason,
                    ],
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], 403);
            }

            $user->tokens()->delete();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'data' => [
                    'token' => $token,
                    'user' => new UserResource($user),
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Login failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Login failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    public function logout(): JsonResponse
    {
        try {
            $user = Auth::guard('sanctum')->user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthenticated',
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], 401);
            }

            $user->currentAccessToken()->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Logout successful',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Logout failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Logout failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    public function resendVerification(): JsonResponse
    {
        try {
            $user = Auth::guard('sanctum')->user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthenticated',
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], 401);
            }

            if ($user->hasVerifiedEmail()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Email already verified',
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], 400);
            }

            $user->sendEmailVerificationNotification();

            return response()->json([
                'status' => 'success',
                'message' => 'Verification email sent',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Resend verification failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send verification email. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    public function verifyEmail($id, $hash): JsonResponse
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found',
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], 404);
            }

            if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid verification link',
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], 400);
            }

            if ($user->hasVerifiedEmail()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Email already verified',
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], 200);
            }

            $user->markEmailAsVerified();

            return response()->json([
                'status' => 'success',
                'message' => 'Email verified successfully',
                'data' => [
                    'user' => new UserResource($user),
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Email verification failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Email verification failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Password reset link sent to your email',
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], 200);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Unable to send reset link. Please try again.',
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 400);
        } catch (\Exception $e) {
            \Log::error('Forgot password failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send reset link. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->password = $password;
                    $user->save();

                    $user->tokens()->delete();
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Password reset successfully',
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], 200);
            }

            return response()->json([
                'status' => 'error',
                'message' => __($status),
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 400);
        } catch (\Exception $e) {
            \Log::error('Reset password failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to reset password. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    public function redirectToGoogle(): JsonResponse
    {
        try {
            $url = Socialite::driver('google')
                ->stateless()
                ->redirect()
                ->getTargetUrl();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'url' => $url,
                ],
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 200);
        } catch (\Exception $e) {
            \Log::error('Google redirect failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to initiate Google login. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null,
                'meta' => [
                    'timestamp' => now()->toIso8601String(),
                ],
            ], 500);
        }
    }

    public function handleGoogleCallback(): \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
    {
        try {
            $googleUser = Socialite::driver('google')->stateless()->user();

            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {
                if ($user->is_banned) {
                    if (request()->expectsJson()) {
                        return response()->json([
                            'status' => 'error',
                            'message' => 'Your account has been banned',
                            'meta' => [
                                'timestamp' => now()->toIso8601String(),
                            ],
                        ], 403);
                    }

                    return redirect()->route('login')->with('error', 'Your account has been banned.');
                }

                if (!$user->email_verified_at) {
                    $user->update([
                        'email_verified_at' => now(),
                    ]);
                }
            } else {
                $username = Str::slug($googleUser->getName()) . '-' . Str::random(6);

                $user = User::create([
                    'username' => $username,
                    'email' => $googleUser->getEmail(),
                    'display_name' => $googleUser->getName(),
                    'avatar' => $googleUser->getAvatar(),
                    'email_verified_at' => now(),
                    'password' => Hash::make(Str::random(32)),
                ]);
            }

            $user->tokens()->delete();

            $token = $user->createToken('auth_token')->plainTextToken;

            if (request()->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Google login successful',
                    'data' => [
                        'token' => $token,
                        'user' => new UserResource($user),
                    ],
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ]);
            }

            // Browser flow: hand the token to the frontend callback page
            // via a URL fragment (never in server logs / referrer).
            $payload = base64_encode(json_encode([
                'token' => $token,
                'user' => (new UserResource($user))->resolve(),
            ]));

            return redirect()->route('auth.google.callback')->withFragment($payload);
        } catch (\Exception $e) {
            \Log::error('Google callback failed: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            if (request()->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to complete Google login. Please try again.',
                    'meta' => [
                        'timestamp' => now()->toIso8601String(),
                    ],
                ], 500);
            }

            return redirect()->route('login')->with('error', 'Failed to complete Google login. Please try again.');
        }
    }
}
