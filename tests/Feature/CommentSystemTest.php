<?php

use App\Models\Comment;
use App\Models\Post;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

use Tests\TestCase;

class CommentSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Post $post;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        
        $this->post = Post::factory()->published()->create();
    }

    public function test_user_can_view_post_comments(): void
    {
        Comment::factory()->count(5)->create([
            'post_id' => $this->post->id,
            'is_deleted' => false,
        ]);

        Comment::factory()->count(2)->create([
            'post_id' => $this->post->id,
            'is_deleted' => true,
        ]);

        $response = $this->getJson("/api/v1/posts/{$this->post->id}/comments");

        $response->assertOk();
        $this->assertCount(5, $response->json('data'));
    }

    public function test_comments_are_sorted_by_vote_score(): void
    {
        Comment::factory()->create(['post_id' => $this->post->id, 'vote_score' => 100, 'is_deleted' => false]);
        Comment::factory()->create(['post_id' => $this->post->id, 'vote_score' => 50, 'is_deleted' => false]);
        Comment::factory()->create(['post_id' => $this->post->id, 'vote_score' => 80, 'is_deleted' => false]);
        Comment::factory()->create(['post_id' => $this->post->id, 'vote_score' => 10, 'is_deleted' => false]);

        $response = $this->getJson("/api/v1/posts/{$this->post->id}/comments");

        $response->assertOk();
        $data = $response->json('data');
        
        $this->assertEquals(100, $data[0]['vote_score']);
        $this->assertEquals(80, $data[1]['vote_score']);
        $this->assertEquals(50, $data[2]['vote_score']);
        $this->assertEquals(10, $data[3]['vote_score']);
    }

    public function test_comments_are_nested_with_replies(): void
    {
        $parent = Comment::factory()->create(['post_id' => $this->post->id, 'is_deleted' => false]);
        $reply1 = Comment::factory()->create(['post_id' => $this->post->id, 'parent_id' => $parent->id, 'is_deleted' => false]);
        $reply2 = Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => $parent->id,
            'is_deleted' => true,
        ]);

        Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => $reply1->id,
            'is_deleted' => false,
        ]);

        Comment::factory()->create([
            'post_id' => $this->post->id,
            'parent_id' => $reply1->id,
            'is_deleted' => true,
        ]);

        $response = $this->getJson("/api/v1/posts/{$this->post->id}/comments");

        $response->assertOk();
        $data = $response->json('data');

        // Parent has exactly 1 non-deleted reply (reply1)
        $this->assertDatabaseHas('comments', [
            'parent_id' => $parent->id,
            'is_deleted' => false,
        ]);
        $this->assertSame(
            1,
            \App\Models\Comment::where('parent_id', $parent->id)->where('is_deleted', false)->count()
        );
        // Data list only contains top-level comments (parent)
        $this->assertCount(1, $data);
    }

    public function test_guest_can_view_post_comments(): void
    {
        Comment::factory()->create(['post_id' => $this->post->id, 'is_deleted' => false]);

        $response = $this->getJson("/api/v1/posts/{$this->post->id}/comments");

        $response->assertOk();
    }

    public function test_user_can_create_top_level_comment(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/posts/{$this->post->id}/comments", [
            'content' => 'This is a test comment!',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('comments', [
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'parent_id' => null,
            'content' => 'This is a test comment!',
        ]);
    }

    public function test_user_cannot_create_comment_on_unpublished_post(): void
    {
        $unpublishedPost = Post::factory()->draft()->create();

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/posts/{$unpublishedPost->id}/comments", [
            'content' => 'This should fail!',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_reply_to_comment(): void
    {
        $parent = Comment::factory()->create(['post_id' => $this->post->id, 'is_deleted' => false]);

        Sanctum::actingAs($this->user);

        $response = $this->postJson("/api/v1/comments/{$parent->id}/replies", [
            'content' => 'This is a reply!',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('comments', [
            'user_id' => $this->user->id,
            'post_id' => $this->post->id,
            'parent_id' => $parent->id,
            'depth' => 1,
            'content' => 'This is a reply!',
        ]);
    }

    public function test_user_can_edit_own_comment(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'is_deleted' => false,
            'user_id' => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->putJson("/api/v1/comments/{$comment->id}", [
            'content' => 'Updated content!',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Updated content!',
        ]);
    }

    public function test_user_cannot_edit_another_user_comment(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'is_deleted' => false,
            'user_id' => $this->otherUser->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->putJson("/api/v1/comments/{$comment->id}", [
            'content' => 'Unauthorized edit!',
        ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_own_comment(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'is_deleted' => false,
            'user_id' => $this->user->id,
        ]);

        Sanctum::actingAs($this->user);

        $response = $this->deleteJson("/api/v1/comments/{$comment->id}");

        $response->assertOk();
        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    }

    public function test_unauthorized_user_cannot_delete_comment(): void
    {
        $comment = Comment::factory()->create([
            'post_id' => $this->post->id,
            'is_deleted' => false,
        ]);

        Sanctum::actingAs($this->otherUser);

        $response = $this->deleteJson("/api/v1/comments/{$comment->id}");

        $response->assertStatus(403);
    }
}
