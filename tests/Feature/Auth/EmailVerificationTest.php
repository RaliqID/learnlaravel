<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_notification_sent_on_registration(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/v1/auth/register', [
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'john@example.com')->first();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_user_can_verify_email_with_valid_link(): void
    {
        $user = User::factory()->unverified()->create();

        $this->assertNull($user->email_verified_at);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Email verified successfully',
            ]);

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_user_cannot_verify_email_with_invalid_hash(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => 'invalid-hash']
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Invalid verification link',
            ]);

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_already_verified_email_returns_success(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->email_verified_at);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Email already verified',
            ]);
    }

    public function test_authenticated_user_can_resend_verification_email(): void
    {
        Notification::fake();

        $user = User::factory()->unverified()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/email/resend');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Verification email sent',
            ]);

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_unauthenticated_user_cannot_resend_verification_email(): void
    {
        $response = $this->postJson('/api/v1/auth/email/resend');

        $response->assertStatus(401);
    }

    public function test_verified_user_cannot_resend_verification_email(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/email/resend');

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Email already verified',
            ]);
    }

    public function test_user_starts_unverified_after_registration(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'john@example.com')->first();

        $this->assertNull($user->email_verified_at);
    }

    public function test_verification_link_with_wrong_user_id_fails(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => 99999, 'hash' => sha1($user->email)]
        );

        $response = $this->getJson($verificationUrl);

        $response->assertStatus(404);
    }
}
