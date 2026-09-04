<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'id' => fake()->uuid(),
            'user_id' => User::factory(),
            'type' => Notification::TYPE_POST_VOTED,
            'notifiable_type' => Post::class,
            'notifiable_id' => Post::factory(),
            'data' => [
                'message' => fake()->sentence(),
            ],
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn () => ['read_at' => now()]);
    }

    public function unread(): static
    {
        return $this->state(fn () => ['read_at' => null]);
    }
}
