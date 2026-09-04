<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class GoogleOAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_redirect_returns_authorization_url(): void
    {
        $response = $this->getJson('/api/v1/auth/google');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    'url',
                ],
                'meta' => ['timestamp'],
            ])
            ->assertJson([
                'status' => 'success',
            ]);

        $this->assertStringContainsString('accounts.google.com', $response->json('data.url'));
    }

    public function test_google_callback_creates_new_user(): void
    {
        $googleUser = $this->mockSocialiteUser();

        Socialite::shouldReceive('driver->stateless->user')
            ->once()
            ->andReturn($googleUser);

        $response = $this->getJson('/api/v1/auth/google/callback');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'token',
                    'user' => [
                        'id',
                        'username',
                        'email',
                        'display_name',
                    ],
                ],
                'meta' => ['timestamp'],
            ])
            ->assertJson([
                'status' => 'success',
                'message' => 'Google login successful',
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'john.doe@gmail.com',
            'display_name' => 'John Doe',
        ]);

        $user = User::where('email', 'john.doe@gmail.com')->first();
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_google_callback_logs_in_existing_user(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'john.doe@gmail.com',
            'username' => 'johndoe',
        ]);

        $googleUser = $this->mockSocialiteUser();

        Socialite::shouldReceive('driver->stateless->user')
            ->once()
            ->andReturn($googleUser);

        $response = $this->getJson('/api/v1/auth/google/callback');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Google login successful',
            ]);

        $this->assertEquals(1, User::where('email', 'john.doe@gmail.com')->count());
    }

    public function test_google_callback_verifies_email_for_existing_unverified_user(): void
    {
        $existingUser = User::factory()->unverified()->create([
            'email' => 'john.doe@gmail.com',
        ]);

        $this->assertNull($existingUser->email_verified_at);

        $googleUser = $this->mockSocialiteUser();

        Socialite::shouldReceive('driver->stateless->user')
            ->once()
            ->andReturn($googleUser);

        $response = $this->getJson('/api/v1/auth/google/callback');

        $response->assertStatus(200);

        $existingUser->refresh();
        $this->assertNotNull($existingUser->email_verified_at);
    }

    public function test_google_callback_revokes_previous_tokens(): void
    {
        $existingUser = User::factory()->create([
            'email' => 'john.doe@gmail.com',
        ]);

        $existingUser->createToken('old_token_1');
        $existingUser->createToken('old_token_2');

        $this->assertEquals(2, $existingUser->tokens()->count());

        $googleUser = $this->mockSocialiteUser();

        Socialite::shouldReceive('driver->stateless->user')
            ->once()
            ->andReturn($googleUser);

        $response = $this->getJson('/api/v1/auth/google/callback');

        $response->assertStatus(200);

        $this->assertEquals(1, $existingUser->fresh()->tokens()->count());
    }

    public function test_banned_user_cannot_login_with_google(): void
    {
        $bannedUser = User::factory()->banned()->create([
            'email' => 'john.doe@gmail.com',
        ]);

        $googleUser = $this->mockSocialiteUser();

        Socialite::shouldReceive('driver->stateless->user')
            ->once()
            ->andReturn($googleUser);

        $response = $this->getJson('/api/v1/auth/google/callback');

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'Your account has been banned',
            ]);
    }

    public function test_google_callback_returns_token_and_user_data(): void
    {
        $googleUser = $this->mockSocialiteUser();

        Socialite::shouldReceive('driver->stateless->user')
            ->once()
            ->andReturn($googleUser);

        $response = $this->getJson('/api/v1/auth/google/callback');

        $response->assertStatus(200);

        $this->assertNotNull($response->json('data.token'));
        $this->assertEquals('john.doe@gmail.com', $response->json('data.user.email'));
        $this->assertEquals('John Doe', $response->json('data.user.display_name'));
    }

    private function mockSocialiteUser(): SocialiteUser
    {
        $user = new SocialiteUser();
        $user->map([
            'id' => '1234567890',
            'email' => 'john.doe@gmail.com',
            'name' => 'John Doe',
            'avatar' => 'https://lh3.googleusercontent.com/a/default-user',
        ]);

        return $user;
    }
}
