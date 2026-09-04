<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_endpoint_has_rate_limit(): void
    {
        $user = User::factory()->create([
            'email' => 'john@example.com',
            'password' => 'Password123!',
        ]);

        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'email' => 'john@example.com',
                'password' => 'Password123!',
            ]);

            if ($i < 10) {
                $this->assertContains($response->status(), [200, 401]);
            }
        }

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(429);
    }

    public function test_register_endpoint_has_rate_limit(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/v1/auth/register', [
                'username' => 'user' . $i,
                'email' => "user{$i}@example.com",
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

            $this->assertContains($response->status(), [201, 422]);
        }

        $response = $this->postJson('/api/v1/auth/register', [
            'username' => 'user99',
            'email' => 'user99@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(429);
    }

    public function test_forgot_password_endpoint_has_rate_limit(): void
    {
        $user = User::factory()->create(['email' => 'john@example.com']);

        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/v1/auth/forgot-password', [
                'email' => 'john@example.com',
            ]);

            $this->assertContains($response->status(), [200, 400, 422, 500]);
        }

        $response = $this->postJson('/api/v1/auth/forgot-password', [
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(429);
    }

    public function test_authenticated_requests_have_higher_rate_limit(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $successCount = 0;
        for ($i = 0; $i < 65; $i++) {
            $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->getJson('/api/v1/user');

            if ($response->status() === 200) {
                $successCount++;
            }

            if ($response->status() === 429) {
                break;
            }
        }

        $this->assertGreaterThanOrEqual(60, $successCount);
    }

    public function test_rate_limit_headers_are_present(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
    }

    public function test_reset_password_has_strict_rate_limit(): void
    {
        $user = User::factory()->create(['email' => 'john@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/v1/auth/reset-password', [
                'token' => 'fake-token',
                'email' => 'john@example.com',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

            $this->assertContains($response->status(), [400, 422]);
        }

        $response = $this->postJson('/api/v1/auth/reset-password', [
            'token' => 'fake-token',
            'email' => 'john@example.com',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertStatus(429);
    }
}
