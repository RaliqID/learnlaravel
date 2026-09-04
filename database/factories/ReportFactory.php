<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['post', 'comment']);

        return [
            'reporter_id' => User::factory(),
            'reportable_type' => $type === 'post' ? Post::class : Comment::class,
            'reportable_id' => $type === 'post' ? Post::factory() : Comment::factory(),
            'reason' => fake()->randomElement(['spam', 'harassment', 'hateful', 'violence', 'nsfw', 'misinformation', 'other']),
            'description' => fake()->optional()->sentence(),
            'status' => Report::STATUS_PENDING,
            'resolved_by' => null,
            'resolved_at' => null,
            'resolution_note' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => Report::STATUS_PENDING,
            'resolved_by' => null,
            'resolved_at' => null,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn () => [
            'status' => Report::STATUS_RESOLVED,
            'resolved_by' => User::factory(),
            'resolved_at' => now(),
        ]);
    }
}
