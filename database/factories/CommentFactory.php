<?php

namespace Database\Factories;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'post_id' => \App\Models\Post::factory(),
            'content' => $this->faker->paragraph(),
            'vote_score' => 0,
            'upvote_count' => 0,
            'downvote_count' => 0,
            'reply_count' => 0,
            'depth' => 0,
            'is_deleted' => false,
        ];
    }

    public function topLevel(): static
    {
        return $this->state(fn () => ['parent_id' => null, 'reply_count' => 0, 'depth' => 0]);
    }

    public function reply(): static
    {
        return $this->state(fn () => ['parent_id' => Comment::factory()]);
    }

    public function deleted(): static
    {
        return $this->state(fn () => ['is_deleted' => true]);
    }

    public function onPost(): static
    {
        return $this->state(fn () => ['post_id' => \App\Models\Post::factory()]);
    }
}
