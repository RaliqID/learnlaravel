<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_text_post_as_published(): void
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create(['post_count' => 0]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/posts', [
            'title' => 'This is a valid text post title',
            'content' => 'This is the content of the post. **Markdown** supported.',
            'post_type' => 'text',
            'topic_id' => $topic->id,
            'status' => 'published',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Post published successfully',
            ]);

        $this->assertDatabaseHas('posts', [
            'user_id' => $user->id,
            'topic_id' => $topic->id,
            'title' => 'This is a valid text post title',
            'status' => 'published',
            'post_type' => 'text',
            'content_html' => "<p>This is the content of the post. <strong>Markdown</strong> supported.</p>\n",
        ]);

        // Counter check
        $this->assertEquals(1, $topic->fresh()->post_count);
    }

    public function test_user_can_create_draft_post(): void
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create(['post_count' => 0]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/posts', [
            'title' => 'Draft post title here',
            'content' => 'Draft content',
            'post_type' => 'text',
            'topic_id' => $topic->id,
            'status' => 'draft',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Draft saved successfully',
            ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'Draft post title here',
            'status' => 'draft',
            'published_at' => null,
        ]);

        // Draft should not increase counter
        $this->assertEquals(0, $topic->fresh()->post_count);
    }

    public function test_user_can_create_link_post(): void
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/posts', [
            'title' => 'Check out this awesome link',
            'url' => 'https://laravel.com/docs',
            'post_type' => 'link',
            'topic_id' => $topic->id,
            'status' => 'published',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('posts', [
            'url' => 'https://laravel.com/docs',
            'post_type' => 'link',
        ]);
    }

    public function test_user_can_create_image_post(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $topic = Topic::factory()->create();
        Sanctum::actingAs($user);

        $image = UploadedFile::fake()->image('photo.jpg', 800, 600);

        $response = $this->postJson('/api/v1/posts', [
            'title' => 'Look at this photo',
            'post_type' => 'image',
            'topic_id' => $topic->id,
            'status' => 'published',
            'image' => $image,
        ]);

        $response->assertStatus(201);
        $post = Post::latest()->first();

        $this->assertNotNull($post->image_url);
        $this->assertNotNull($post->thumbnail_url);
    }

    public function test_duplicate_link_is_rejected(): void
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create();
        $existingPost = Post::factory()->published()->create([
            'url' => 'https://example.com/unique',
            'post_type' => 'link',
        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/posts', [
            'title' => 'Trying to post same link',
            'url' => 'https://example.com/unique',
            'post_type' => 'link',
            'topic_id' => $topic->id,
            'status' => 'published',
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'status' => 'error',
                'message' => 'This link has already been posted',
            ]);
    }

    public function test_user_can_update_own_post(): void
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'title' => 'Original Title 1234',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/posts/{$post->id}", [
            'title' => 'Updated Title 1234',
            'topic_id' => $topic->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('posts', [
            'id' => $post->id,
            'title' => 'Updated Title 1234',
            'topic_id' => $topic->id,
        ]);
    }

    public function test_user_cannot_update_other_users_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/posts/{$post->id}", [
            'title' => 'Hacked Title 1234',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_any_post(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create();

        Sanctum::actingAs($admin);

        $response = $this->putJson("/api/v1/posts/{$post->id}", [
            'title' => 'Admin edited this',
        ]);

        $response->assertOk();
    }

    public function test_publishing_draft_updates_timestamp_and_counter(): void
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create(['post_count' => 0]);
        $post = Post::factory()->draft()->create([
            'user_id' => $user->id,
            'topic_id' => $topic->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/posts/{$post->id}", [
            'status' => 'published',
        ]);

        $response->assertOk();
        $this->assertEquals('published', $post->fresh()->status);
        $this->assertNotNull($post->fresh()->published_at);
        $this->assertEquals(1, $topic->fresh()->post_count);
    }

    public function test_user_can_delete_own_post(): void
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create(['post_count' => 1]);
        $post = Post::factory()->published()->create([
            'user_id' => $user->id,
            'topic_id' => $topic->id,
        ]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/posts/{$post->id}");

        $response->assertOk();
        $this->assertSoftDeleted('posts', ['id' => $post->id]);
        $this->assertEquals(0, $topic->fresh()->post_count);
    }

    public function test_user_can_get_their_own_posts(): void
    {
        $user = User::factory()->create();
        Post::factory()->draft()->count(3)->create(['user_id' => $user->id]);
        Post::factory()->published()->count(2)->create(['user_id' => $user->id]);

        Sanctum::actingAs($user);

        // Check drafts
        $drafts = $this->getJson('/api/v1/user/posts?status=draft');
        $drafts->assertOk();
        $this->assertCount(3, $drafts->json('data.posts'));

        // Check published
        $published = $this->getJson('/api/v1/user/posts?status=published');
        $published->assertOk();
        $this->assertCount(2, $published->json('data.posts'));
    }

    public function test_post_detail_shows_full_content(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'Test Post View',
            'content' => '# Hello World',
            'content_html' => '<h1>Hello World</h1>',
        ]);

        $response = $this->getJson("/api/v1/posts/{$post->slug}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'post' => [
                        'title' => 'Test Post View',
                        'content' => '# Hello World',
                        'content_html' => '<h1>Hello World</h1>',
                    ],
                ],
            ]);
    }

    public function test_unauthenticated_user_cannot_create_post(): void
    {
        $response = $this->postJson('/api/v1/posts', [
            'title' => 'Valid Title 1234',
        ]);

        $response->assertStatus(401);
    }
}
