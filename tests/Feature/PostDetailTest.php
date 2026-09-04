<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_published_post_detail(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'A nice readable post title',
            'content' => '**Bold** and _italic_ content.',
            'content_html' => "<p><strong>Bold</strong> and <em>italic</em> content.</p>\n",
            'meta_title' => 'SEO Title',
            'meta_description' => 'SEO Description',
        ]);

        $response = $this->getJson("/api/v1/posts/{$post->slug}");

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [
                    'post' => [
                        'id',
                        'title',
                        'slug',
                        'content',
                        'content_html',
                        'reading_time',
                        'canonical_url',
                        'share_url',
                        'meta_title',
                        'meta_description',
                        'user',
                        'topic',
                    ],
                    'related_posts',
                    'navigation' => ['previous', 'next'],
                ],
                'meta' => ['timestamp'],
            ])
            ->assertJson([
                'data' => [
                    'post' => [
                        'title' => 'A nice readable post title',
                        'meta_title' => 'SEO Title',
                        'meta_description' => 'SEO Description',
                    ],
                ],
            ]);
    }

    public function test_view_counter_increments_on_view(): void
    {
        $post = Post::factory()->published()->create(['view_count' => 5]);

        $this->getJson("/api/v1/posts/{$post->slug}")->assertOk();
        $this->getJson("/api/v1/posts/{$post->slug}")->assertOk();

        $this->assertEquals(7, $post->fresh()->view_count);
    }

    public function test_reading_time_is_returned(): void
    {
        $content = implode(' ', array_fill(0, 400, 'word'));
        $post = Post::factory()->published()->create(['content' => $content]);

        $response = $this->getJson("/api/v1/posts/{$post->slug}");

        $response->assertOk();
        $this->assertEquals(2, $response->json('data.post.reading_time'));
    }

    public function test_related_posts_are_from_same_topic_and_exclude_self(): void
    {
        $topic = Topic::factory()->create();
        $post = Post::factory()->published()->create(['topic_id' => $topic->id]);
        $related = Post::factory()->published()->count(3)->create(['topic_id' => $topic->id]);
        $otherTopic = Post::factory()->published()->create();

        $response = $this->getJson("/api/v1/posts/{$post->slug}");

        $response->assertOk();
        $ids = collect($response->json('data.related_posts'))->pluck('id');

        $this->assertCount(3, $ids);
        $this->assertNotContains($post->id, $ids->all());
        $this->assertNotContains($otherTopic->id, $ids->all());
        foreach ($related as $r) {
            $this->assertContains($r->id, $ids->all());
        }
    }

    public function test_navigation_returns_previous_and_next(): void
    {
        $older = Post::factory()->published()->create(['published_at' => now()->subDays(2)]);
        $current = Post::factory()->published()->create(['published_at' => now()->subDay()]);
        $newer = Post::factory()->published()->create(['published_at' => now()]);

        $response = $this->getJson("/api/v1/posts/{$current->slug}");

        $response->assertOk();
        $this->assertEquals($older->id, $response->json('data.navigation.previous.id'));
        $this->assertEquals($newer->id, $response->json('data.navigation.next.id'));
    }

    public function test_guest_cannot_view_draft(): void
    {
        $post = Post::factory()->draft()->create();

        $response = $this->getJson("/api/v1/posts/{$post->slug}");

        $response->assertStatus(403);
    }

    public function test_owner_can_view_own_draft(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->draft()->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        $this->getJson("/api/v1/posts/{$post->slug}")->assertOk();
    }

    public function test_other_user_cannot_view_draft(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $post = Post::factory()->draft()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($other);

        $response = $this->getJson("/api/v1/posts/{$post->slug}");

        $response->assertStatus(403);
    }

    public function test_deleted_post_returns_404(): void
    {
        $post = Post::factory()->published()->create();
        $post->delete();

        $this->getJson("/api/v1/posts/{$post->slug}")->assertStatus(404);
    }

    public function test_post_not_found_returns_404(): void
    {
        $this->getJson('/api/v1/posts/this-slug-does-not-exist')->assertStatus(404);
    }

    public function test_detail_includes_author_and_topic_info(): void
    {
        $user = User::factory()->create(['username' => 'authoruser']);
        $topic = Topic::factory()->create(['name' => 'Laravel']);
        $post = Post::factory()->published()->create([
            'user_id' => $user->id,
            'topic_id' => $topic->id,
        ]);

        $response = $this->getJson("/api/v1/posts/{$post->slug}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'post' => [
                        'user' => ['username' => 'authoruser'],
                        'topic' => ['name' => 'Laravel'],
                    ],
                ],
            ]);
    }

    public function test_detail_supports_id_identifier(): void
    {
        $post = Post::factory()->published()->create();

        $this->getJson("/api/v1/posts/{$post->id}")->assertOk();
    }
}
