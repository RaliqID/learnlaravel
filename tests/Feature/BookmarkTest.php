<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookmarkTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->post = Post::factory()->published()->create(['bookmark_count' => 0]);
    }

    public function test_user_can_bookmark_post(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/posts/{$this->post->id}/bookmark", [
            'collection_name' => 'Laravel',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Post bookmarked successfully',
            ]);

        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'collection_name' => 'Laravel',
        ]);

        $this->assertEquals(1, $this->post->fresh()->bookmark_count);
    }

    public function test_guest_cannot_bookmark_post(): void
    {
        $this->postJson("/api/v1/posts/{$this->post->id}/bookmark")
            ->assertStatus(401);
    }

    public function test_user_cannot_bookmark_unpublished_post(): void
    {
        $draft = Post::factory()->draft()->create();

        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/posts/{$draft->id}/bookmark")
            ->assertStatus(422);
    }

    public function test_bookmark_is_idempotent_and_updates_collection(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/posts/{$this->post->id}/bookmark", ['collection_name' => 'First'])
            ->assertStatus(201);

        $this->postJson("/api/v1/posts/{$this->post->id}/bookmark", ['collection_name' => 'Second'])
            ->assertStatus(201);

        $this->assertEquals(1, Bookmark::where('user_id', $this->user->id)->count());
        $this->assertEquals('Second', Bookmark::where('user_id', $this->user->id)->value('collection_name'));
        $this->assertEquals(1, $this->post->fresh()->bookmark_count);
    }

    public function test_user_can_unbookmark_post(): void
    {
        Bookmark::factory()->create([
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
        ]);
        $this->post->update(['bookmark_count' => 1]);

        Sanctum::actingAs($this->user);

        $this->deleteJson("/api/v1/posts/{$this->post->id}/bookmark")
            ->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Bookmark removed',
            ]);

        $this->assertDatabaseMissing('bookmarks', [
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
        ]);
        $this->assertEquals(0, $this->post->fresh()->bookmark_count);
    }

    public function test_unbookmark_when_not_bookmarked_is_idempotent(): void
    {
        Sanctum::actingAs($this->user);

        $this->deleteJson("/api/v1/posts/{$this->post->id}/bookmark")
            ->assertOk()
            ->assertJson(['message' => 'Post is not bookmarked']);

        $this->assertEquals(0, $this->post->fresh()->bookmark_count);
    }

    public function test_user_can_list_own_bookmarks(): void
    {
        $postA = Post::factory()->published()->create();
        $postB = Post::factory()->published()->create();

        Bookmark::factory()->create(['user_id' => $this->user->id, 'post_id' => $postA->id]);
        Bookmark::factory()->create(['user_id' => $this->user->id, 'post_id' => $postB->id]);
        Bookmark::factory()->create(['user_id' => User::factory()->create()->id, 'post_id' => $postA->id]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/bookmarks');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'collection_name',
                        'created_at',
                        'post' => ['id', 'title', 'slug'],
                    ],
                ],
                'meta' => ['pagination', 'timestamp'],
            ]);

        $this->assertCount(2, $response->json('data'));
        $this->assertEquals(2, $response->json('meta.pagination.total'));
    }

    public function test_user_can_filter_bookmarks_by_collection(): void
    {
        $postA = Post::factory()->published()->create();
        $postB = Post::factory()->published()->create();

        Bookmark::factory()->inCollection('Laravel')->create(['user_id' => $this->user->id, 'post_id' => $postA->id]);
        Bookmark::factory()->inCollection('PHP')->create(['user_id' => $this->user->id, 'post_id' => $postB->id]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/bookmarks?collection=Laravel');

        $response->assertOk();
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('Laravel', $response->json('data.0.collection_name'));
    }

    public function test_user_can_remove_bookmark_by_id(): void
    {
        $bookmark = Bookmark::factory()->create([
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
        ]);
        $this->post->update(['bookmark_count' => 1]);

        Sanctum::actingAs($this->user);

        $this->deleteJson("/api/v1/bookmarks/{$bookmark->id}")
            ->assertOk();

        $this->assertDatabaseMissing('bookmarks', ['id' => $bookmark->id]);
        $this->assertEquals(0, $this->post->fresh()->bookmark_count);
    }

    public function test_user_cannot_remove_others_bookmark(): void
    {
        $owner = User::factory()->create();
        $bookmark = Bookmark::factory()->create([
            'user_id' => $owner->id,
            'post_id' => $this->post->id,
        ]);

        Sanctum::actingAs($this->user);

        $this->deleteJson("/api/v1/bookmarks/{$bookmark->id}")
            ->assertStatus(403);
    }

    public function test_user_can_list_bookmark_collections(): void
    {
        $postA = Post::factory()->published()->create();
        $postB = Post::factory()->published()->create();
        $postC = Post::factory()->published()->create();

        Bookmark::factory()->inCollection('Laravel')->create(['user_id' => $this->user->id, 'post_id' => $postA->id]);
        Bookmark::factory()->inCollection('Laravel')->create(['user_id' => $this->user->id, 'post_id' => $postB->id]);
        Bookmark::factory()->inCollection('PHP')->create(['user_id' => $this->user->id, 'post_id' => $postC->id]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/bookmarks/collections');

        $response->assertOk();
        $collections = collect($response->json('data'));

        $this->assertEquals('Laravel', $collections->firstWhere('name', 'Laravel')['name']);
        $this->assertEquals(2, $collections->firstWhere('name', 'Laravel')['total']);
        $this->assertEquals(1, $collections->firstWhere('name', 'PHP')['total']);
    }
}
