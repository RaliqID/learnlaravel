<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostVoteTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['karma_score' => 10]);
        $this->post = Post::factory()->published()->create([
            'vote_score' => 10,
            'upvote_count' => 10,
            'downvote_count' => 0,
            'hot_score' => 50,
            'topic_id' => Topic::factory()->create()->id,
        ]);
    }

    public function test_upvote_changes_score(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/posts/{$this->post->id}/vote");

        $response->assertOk();
        $this->assertEquals(11, $this->post->fresh()->vote_score);
        $this->assertEquals(11, $this->post->fresh()->upvote_count);
        $this->assertEquals(0, $this->post->fresh()->downvote_count);
        // Should return updated vote score
        $this->assertEquals(11, $response->json('data.vote_score'));
    }

    public function test_downvote_changes_score(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/posts/{$this->post->id}/vote/down");

        $response->assertOk();
        $this->assertEquals(9, $this->post->fresh()->vote_score);
        $this->assertEquals(1, $this->post->fresh()->downvote_count);
        $this->assertEquals(10, $this->post->fresh()->upvote_count);
        $this->assertEquals(9, $response->json('data.vote_score'));
    }

    public function test_user_can_change_vote_from_upvote_to_downvote(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/posts/{$this->post->id}/vote")->assertOk();
        $this->assertEquals(11, $this->post->fresh()->vote_score);
        $this->assertEquals(11, $this->post->fresh()->upvote_count);

        $this->postJson("/api/v1/posts/{$this->post->id}/vote/down")->assertOk();
        $this->assertEquals(9, $this->post->fresh()->vote_score);
        $this->assertEquals(10, $this->post->fresh()->upvote_count);
        $this->assertEquals(1, $this->post->fresh()->downvote_count);
    }

    public function test_user_can_remove_vote(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/posts/{$this->post->id}/vote")->assertOk();
        $this->assertEquals(11, $this->post->fresh()->vote_score);
        $this->assertEquals(11, $this->post->fresh()->upvote_count);

        $this->deleteJson("/api/v1/posts/{$this->post->id}/vote")->assertOk();
        $this->assertEquals(10, $this->post->fresh()->vote_score);
        $this->assertEquals(10, $this->post->fresh()->upvote_count);
        $this->assertNull($this->post->fresh()->votes()->where('user_id', $this->user->id)->first());
    }

    public function test_upvote_is_idempotent(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/posts/{$this->post->id}/vote")->assertOk();
        $this->postJson("/api/v1/posts/{$this->post->id}/vote")->assertOk();

        $this->assertEquals(11, $this->post->fresh()->vote_score);
        $this->assertEquals(11, $this->post->fresh()->upvote_count);
    }

    public function test_remove_nonexistent_vote_is_noop(): void
    {
        Sanctum::actingAs($this->user);

        $this->deleteJson("/api/v1/posts/{$this->post->id}/vote")->assertOk();

        $this->assertEquals(10, $this->post->fresh()->vote_score);
    }

    public function test_guest_cannot_vote(): void
    {
        $this->postJson("/api/v1/posts/{$this->post->id}/vote")->assertStatus(401);
        $this->postJson("/api/v1/posts/{$this->post->id}/vote/down")->assertStatus(401);
        $this->deleteJson("/api/v1/posts/{$this->post->id}/vote")->assertStatus(401);
    }

    public function test_banned_user_cannot_vote(): void
    {
        $banned = User::factory()->banned()->create();
        Sanctum::actingAs($banned);

        $this->postJson("/api/v1/posts/{$this->post->id}/vote")->assertStatus(403);
    }

    public function test_cannot_vote_on_draft_post(): void
    {
        $draft = Post::factory()->draft()->create(['topic_id' => Topic::factory()->create()->id]);
        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/posts/{$draft->id}/vote")->assertStatus(404);
    }

    public function test_cannot_vote_on_locked_post(): void
    {
        $locked = Post::factory()->published()->create(['is_locked' => true, 'topic_id' => Topic::factory()->create()->id]);
        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/posts/{$locked->id}/vote")->assertStatus(422);
    }

    public function test_voting_on_deleted_post_returns_404(): void
    {
        $this->post->delete();
        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/posts/{$this->post->id}/vote")->assertStatus(404);
    }

    public function test_voting_on_nonexistent_post_returns_404(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/posts/999999/vote')->assertStatus(404);
    }

    public function test_karma_updates_on_vote(): void
    {
        $otherUser = User::factory()->create();
        $post = Post::factory()->published()->create(['user_id' => $otherUser->id, 'topic_id' => Topic::factory()->create()->id]);
        $karmaBefore = $otherUser->fresh()->karma_score;

        Sanctum::actingAs($this->user);
        $this->postJson("/api/v1/posts/{$post->id}/vote")->assertOk();

        $this->assertEquals($karmaBefore + 1, $otherUser->fresh()->karma_score);
    }
}
