<?php

namespace Database\Factories;

use App\Models\Bookmark;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookmarkFactory extends Factory
{
    protected $model = Bookmark::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'post_id' => Post::factory(),
            'collection_name' => fake()->optional(0.5)->word(),
            'notes' => fake()->optional(0.5)->sentence(),
        ];
    }

    public function inCollection(string $collection): static
    {
        return $this->state(fn () => ['collection_name' => $collection]);
    }

    public function withNotes(string $notes): static
    {
        return $this->state(fn () => ['notes' => $notes]);
    }
}
