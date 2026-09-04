<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedSectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_hero_returns_headline_and_secondary(): void
    {
        Post::factory()->count(10)->create();

        $response = $this->getJson('/api/v1/feed/hero');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [
                    'headline' => ['id', 'title'],
                    'secondary' => [
                        '*' => ['id', 'title'],
                    ],
                ],
                'meta' => ['timestamp'],
            ]);

        $this->assertNotNull($response->json('data.headline'));
        $this->assertLessThanOrEqual(4, count($response->json('data.secondary')));
    }

    public function test_hero_prioritizes_featured_post(): void
    {
        $featured = Post::factory()->featured()->create();
        Post::factory()->pinned()->create(['published_at' => now()]);
        Post::factory()->create(['hot_score' => 9999, 'published_at' => now()->subHour()]);

        $response = $this->getJson('/api/v1/feed/hero');

        $response->assertOk();
        $this->assertEquals($featured->id, $response->json('data.headline.id'));
    }

    public function test_hero_prioritizes_breaking_when_no_featured(): void
    {
        $breaking = Post::factory()->pinned()->create(['published_at' => now()]);
        Post::factory()->create(['hot_score' => 9999, 'published_at' => now()->subHour()]);

        $response = $this->getJson('/api/v1/feed/hero');

        $response->assertOk();
        $this->assertEquals($breaking->id, $response->json('data.headline.id'));
    }

    public function test_hero_prioritizes_trending_when_no_featured_or_breaking(): void
    {
        $trending = Post::factory()->create(['hot_score' => 9999]);
        Post::factory()->create(['hot_score' => 1, 'published_at' => now()]);

        $response = $this->getJson('/api/v1/feed/hero');

        $response->assertOk();
        $this->assertEquals($trending->id, $response->json('data.headline.id'));
    }

    public function test_hero_falls_back_to_latest(): void
    {
        $latest = Post::factory()->create(['published_at' => now()]);
        Post::factory()->create(['published_at' => now()->subHour()]);

        $response = $this->getJson('/api/v1/feed/hero');

        $response->assertOk();
        $this->assertEquals($latest->id, $response->json('data.headline.id'));
    }

    public function test_trending_returns_top_hot_score(): void
    {
        Post::factory()->create(['hot_score' => 5]);
        Post::factory()->create(['hot_score' => 100]);
        $top = Post::factory()->create(['hot_score' => 500]);

        $response = $this->getJson('/api/v1/feed/trending');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [
                    'trending' => ['*' => ['id', 'title', 'vote_score']],
                ],
                'meta' => ['timestamp'],
            ]);

        $this->assertEquals($top->id, $response->json('data.trending.0.id'));
    }

    public function test_trending_respects_time_range(): void
    {
        $old = Post::factory()->create(['hot_score' => 999, 'published_at' => now()->subDays(30)]);
        $recent = Post::factory()->create(['hot_score' => 500, 'published_at' => now()->subHour()]);

        $response = $this->getJson('/api/v1/feed/trending?time=day');

        $response->assertOk();
        $ids = collect($response->json('data.trending'))->pluck('id');
        $this->assertTrue($ids->contains($recent->id));
        $this->assertNotContains($old->id, $ids->all());
    }

    public function test_editors_picks_returns_only_featured_posts(): void
    {
        Post::factory()->featured()->count(3)->create();
        Post::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/feed/editors-picks');

        $response->assertOk();
        $posts = $response->json('data.posts');

        $this->assertCount(3, $posts);
        foreach ($posts as $post) {
            $this->assertTrue($post['is_featured']);
        }
    }

    public function test_recommended_returns_mixed_unique_posts(): void
    {
        $featured = Post::factory()->featured()->create(['vote_score' => 900]);
        Post::factory()->create(['hot_score' => 800, 'published_at' => now()->subDay()]);
        Post::factory()->count(10)->create();

        $response = $this->getJson('/api/v1/feed/recommended');

        $response->assertOk();
        $ids = collect($response->json('data.posts'))->pluck('id');

        $this->assertLessThanOrEqual(6, $ids->count());
        $this->assertEquals($ids->count(), $ids->unique()->count());
        $this->assertContains($featured->id, $ids->all());
    }

    public function test_categories_returns_active_topics_sorted_by_post_count(): void
    {
        Topic::factory()->create(['name' => 'Small', 'post_count' => 1, 'is_active' => true]);
        Topic::factory()->create(['name' => 'Big', 'post_count' => 900, 'is_active' => true]);
        $inactive = Topic::factory()->inactive()->create(['post_count' => 500]);

        $response = $this->getJson('/api/v1/feed/categories');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [
                    'categories' => ['*' => ['id', 'name', 'slug', 'post_count']],
                ],
                'meta' => ['timestamp'],
            ]);

        $names = array_column($response->json('data.categories'), 'name');

        $this->assertEquals('Big', $names[0]);
        $this->assertContains('Small', $names);
        $this->assertNotContains($inactive->name, $names);
    }

    public function test_sections_return_empty_when_no_data(): void
    {
        $response = $this->getJson('/api/v1/feed/hero');

        $response->assertOk();
        $this->assertNull($response->json('data.headline'));
        $this->assertEmpty($response->json('data.secondary'));
    }
}
