<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\PostVote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PostVote>
 */
class PostVoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'user_id' => User::factory(),
            'vote_type' => fake()->randomElement([1, -1]),
        ];
    }

    public function upvote(): static
    {
        return $this->state(fn (array $attributes) => ['vote_type' => PostVote::UPVOTE]);
    }

    public function downvote(): static
    {
        return $this->state(fn (array $attributes) => ['vote_type' => PostVote::DOWNVOTE]);
    }
}
