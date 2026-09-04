<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    public function definition(): array
    {
        $type = fake()->randomElement(['link', 'text', 'image']);
        $seed = Str::slug(fake()->unique()->words(3, true));

        return [
            'user_id' => User::factory(),
            'topic_id' => Topic::factory(),
            'title' => fake()->sentence(6),
            'slug' => fn () => Str::slug(fake()->unique()->words(4, true).'-'.fake()->numberBetween(100, 999)),
            'content' => fake()->paragraphs(3, true),
            'content_html' => fn (array $attributes) => Str::markdown($attributes['content']),
            'url' => $type === 'link' ? fake()->url() : null,
            'canonical_url' => $type === 'link' ? fake()->url() : null,
            'meta_title' => fn (array $attributes) => Str::limit($attributes['title'], 60),
            'meta_description' => fn (array $attributes) => Str::limit($attributes['content'], 160),
            'post_type' => $type,
            'image_url' => "https://picsum.photos/seed/{$seed}/1200/675",
            'thumbnail_url' => "https://picsum.photos/seed/{$seed}/400/300",
            'vote_score' => fake()->numberBetween(-20, 500),
            'upvote_count' => fake()->numberBetween(0, 300),
            'downvote_count' => fake()->numberBetween(0, 60),
            'comment_count' => fake()->numberBetween(0, 120),
            'view_count' => fake()->numberBetween(0, 5000),
            'bookmark_count' => fake()->numberBetween(0, 200),
            'hot_score' => 0,
            'controversy_score' => 0,
            'is_pinned' => false,
            'is_locked' => false,
            'is_nsfw' => false,
            'is_approved' => true,
            'status' => 'published',
            'featured_at' => null,
            'featured_by' => null,
            'published_at' => now()->subHours(fake()->numberBetween(1, 720)),
            'edited_at' => null,
            'last_activity_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
        ]);
    }

    public function unapproved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => false,
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_locked' => true,
        ]);
    }

    public function nsfw(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_nsfw' => true,
        ]);
    }

    public function pinned(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_pinned' => true,
        ]);
    }

    public function featured(?int $editorId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'featured_at' => now(),
            'featured_by' => $editorId ?? User::factory()->create()->id,
        ]);
    }

    public function publishedAt(string $datetime): static
    {
        return $this->state(fn (array $attributes) => [
            'published_at' => $datetime,
        ]);
    }
}
