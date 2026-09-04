<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\CommentVote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentVoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'comment_id' => Comment::factory(),
            'user_id' => User::factory(),
            'vote_type' => fake()->randomElement([1, -1]),
        ];
    }

    public function upvote(): static
    {
        return $this->state(fn (array $attributes) => ['vote_type' => 1]);
    }

    public function downvote(): static
    {
        return $this->state(fn (array $attributes) => ['vote_type' => -1]);
    }
}
