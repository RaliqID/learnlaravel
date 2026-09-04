<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_valid_data(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'display_name' => 'John Doe',
        ]);

        $response->assertStatus(201)
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
            ]);

        $this->assertDatabaseHas('users', [
            'username' => 'johndoe',
            'email' => 'john@example.com',
        ]);
    }

    public function test_user_cannot_register_with_existing_username(): void
    {
        User::factory()->create(['username' => 'johndoe']);

        $response = $this->postJson('/api/v1/auth/register', [
            'username' => 'johndoe',
            'email' => 'new@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_user_cannot_register_with_existing_email(): void
    {
        User::factory()->create(['email' => 'john@example.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'username' => 'newuser',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_cannot_register_with_invalid_username(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'username' => 'ab',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    }

    public function test_user_cannot_register_with_invalid_email(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'username' => 'johndoe',
            'email' => 'invalid-email',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_user_cannot_register_with_weak_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'password' => 'weak',
            'password_confirmation' => 'weak',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_user_cannot_register_without_password_confirmation(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_user_cannot_register_with_mismatched_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Different123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_username_can_contain_alphanumeric_and_special_chars(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'username' => 'john_doe-123',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'username' => 'john_doe-123',
        ]);
    }

    public function test_display_name_defaults_to_username_if_not_provided(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'username' => 'johndoe',
            'display_name' => 'johndoe',
        ]);
    }
}
