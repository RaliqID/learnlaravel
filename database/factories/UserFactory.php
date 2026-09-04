<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'display_name' => fake()->name(),
            'bio' => fake()->optional()->sentence(),
            'avatar' => null,
            'website' => fake()->optional()->url(),
            'location' => fake()->optional()->city(),
            'karma_score' => 0,
            'post_count' => 0,
            'comment_count' => 0,
            'show_email' => false,
            'email_notifications' => true,
            'is_banned' => false,
            'role' => 'user',
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function moderator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'moderator',
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    public function banned(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_banned' => true,
            'banned_until' => now()->addDays(30),
            'banned_reason' => 'Violation of community guidelines',
        ]);
    }
}
