<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Topic;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_returns_paginated_posts(): void
    {
        Post::factory()->count(25)->create();

        $response = $this->getJson('/api/v1/feed');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [
                    'posts' => [
                        '*' => [
                            'id',
                            'title',
                            'excerpt',
                            'post_type',
                            'vote_score',
                            'comment_count',
                            'is_featured',
                            'published_at',
                            'user' => ['id', 'username'],
                            'topic' => ['id', 'name', 'slug'],
                        ],
                    ],
                ],
                'meta' => [
                    'pagination' => [
                        'total',
                        'count',
                        'per_page',
                        'current_page',
                        'total_pages',
                        'links',
                    ],
                    'timestamp',
                ],
            ]);

        $this->assertCount(20, $response->json('data.posts'));
        $this->assertEquals(25, $response->json('meta.pagination.total'));
    }

    public function test_feed_default_sort_is_newest(): void
    {
        $old = Post::factory()->create(['published_at' => now()->subDays(5)]);
        $new = Post::factory()->create(['published_at' => now()]);

        $response = $this->getJson('/api/v1/feed');

        $response->assertOk();
        $ids = collect($response->json('data.posts'))->pluck('id');
        $this->assertEquals($new->id, $ids->first());
        $this->assertTrue($ids->contains($old->id));
    }

    public function test_feed_can_sort_by_hot(): void
    {
        Post::factory()->create(['hot_score' => 100]);
        $hot = Post::factory()->create(['hot_score' => 999]);

        $response = $this->getJson('/api/v1/feed?sort=hot');

        $response->assertOk();
        $this->assertEquals($hot->id, $response->json('data.posts.0.id'));
    }

    public function test_feed_can_sort_by_top(): void
    {
        Post::factory()->create(['vote_score' => 10]);
        $top = Post::factory()->create(['vote_score' => 999]);

        $response = $this->getJson('/api/v1/feed?sort=top');

        $response->assertOk();
        $this->assertEquals($top->id, $response->json('data.posts.0.id'));
    }

    public function test_feed_filters_by_topic(): void
    {
        $topic = Topic::factory()->create();
        Post::factory()->count(3)->create(['topic_id' => $topic->id]);
        Post::factory()->count(5)->create();

        $response = $this->getJson("/api/v1/feed?topic_id={$topic->id}");

        $response->assertOk();
        $posts = $response->json('data.posts');

        $this->assertCount(3, $posts);
        foreach ($posts as $post) {
            $this->assertEquals($topic->id, $post['topic']['id']);
        }
    }

    public function test_feed_filters_by_time_range(): void
    {
        Post::factory()->create(['published_at' => now()->subHours(2)]);
        Post::factory()->create(['published_at' => now()->subDays(3)]);

        $response = $this->getJson('/api/v1/feed?time=day');

        $response->assertOk();
        $this->assertCount(1, $response->json('data.posts'));
    }

    public function test_feed_rejects_invalid_sort(): void
    {
        $response = $this->getJson('/api/v1/feed?sort=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sort']);
    }

    public function test_feed_rejects_per_page_above_max(): void
    {
        $response = $this->getJson('/api/v1/feed?per_page=101');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    public function test_feed_rejects_non_existent_topic(): void
    {
        $response = $this->getJson('/api/v1/feed?topic_id=99999');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['topic_id']);
    }

    public function test_feed_excludes_unapproved_posts(): void
    {
        $unapproved = Post::factory()->unapproved()->create();

        $response = $this->getJson('/api/v1/feed');

        $response->assertOk();
        $this->assertNotContains($unapproved->id, collect($response->json('data.posts'))->pluck('id'));
    }

    public function test_feed_excludes_locked_posts(): void
    {
        $locked = Post::factory()->locked()->create();

        $response = $this->getJson('/api/v1/feed');

        $response->assertOk();
        $this->assertNotContains($locked->id, collect($response->json('data.posts'))->pluck('id'));
    }

    public function test_feed_returns_empty_result_when_no_published_posts(): void
    {
        Post::factory()->draft()->count(3)->create();

        $response = $this->getJson('/api/v1/feed');

        $response->assertOk();
        $this->assertEmpty($response->json('data.posts'));
        $this->assertEquals(0, $response->json('meta.pagination.total'));
    }

    public function test_feed_avoids_n_plus_one_queries(): void
    {
        $topic = Topic::factory()->create();
        Post::factory()->count(50)->create(['topic_id' => $topic->id]);

        DB::enableQueryLog();

        $this->getJson('/api/v1/feed?per_page=50')
            ->assertOk()
            ->assertJsonCount(50, 'data.posts');

        $this->assertLessThan(10, count(DB::getQueryLog()));
    }
}
