<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Notification;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    public function test_comment_reply_notifies_parent_comment_owner(): void
    {
        $parentOwner = User::factory()->create();
        $post = Post::factory()->published()->create();
        $parent = Comment::factory()->create([
            'user_id' => $parentOwner->id,
            'post_id' => $post->id,
            'is_deleted' => false,
        ]);

        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/comments/{$parent->id}/replies", [
            'content' => 'This is a reply!',
        ])->assertStatus(201);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $parentOwner->id,
            'type' => Notification::TYPE_COMMENT_REPLY,
        ]);
    }

    public function test_top_level_comment_notifies_post_owner(): void
    {
        $postOwner = User::factory()->create();
        $post = Post::factory()->published()->create(['user_id' => $postOwner->id]);

        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/posts/{$post->id}/comments", [
            'content' => 'This is a top-level comment!',
        ])->assertStatus(201);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $postOwner->id,
            'type' => Notification::TYPE_COMMENT_POSTED,
        ]);
    }

    public function test_post_vote_notifies_post_owner(): void
    {
        $postOwner = User::factory()->create();
        $post = Post::factory()->published()->create(['user_id' => $postOwner->id]);

        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/posts/{$post->id}/vote")->assertOk();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $postOwner->id,
            'type' => Notification::TYPE_POST_VOTED,
        ]);
    }

    public function test_self_vote_does_not_create_notification(): void
    {
        $post = Post::factory()->published()->create(['user_id' => $this->user->id]);

        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/posts/{$post->id}/vote")->assertOk();

        $this->assertDatabaseMissing('notifications', [
            'type' => Notification::TYPE_POST_VOTED,
        ]);
    }

    public function test_user_can_list_own_notifications(): void
    {
        Notification::factory()->count(3)->create(['user_id' => $this->user->id]);
        Notification::factory()->count(2)->create(['user_id' => User::factory()->create()->id]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => ['id', 'type', 'data', 'read_at', 'is_read', 'created_at'],
                ],
                'meta' => ['unread_count', 'pagination', 'timestamp'],
            ]);

        $this->assertCount(3, $response->json('data'));
        $this->assertEquals(3, $response->json('meta.pagination.total'));
    }

    public function test_user_can_filter_unread_notifications(): void
    {
        Notification::factory()->unread()->count(2)->create(['user_id' => $this->user->id]);
        Notification::factory()->read()->count(1)->create(['user_id' => $this->user->id]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/notifications?unread=true');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
        $this->assertEquals(2, $response->json('meta.unread_count'));
    }

    public function test_guest_cannot_list_notifications(): void
    {
        $this->getJson('/api/v1/notifications')->assertStatus(401);
    }

    public function test_user_can_get_unread_count(): void
    {
        Notification::factory()->unread()->count(4)->create(['user_id' => $this->user->id]);
        Notification::factory()->read()->count(1)->create(['user_id' => $this->user->id]);

        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/notifications/unread-count');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => ['unread_count' => 4],
            ]);
    }

    public function test_user_can_mark_single_notification_as_read(): void
    {
        $notification = Notification::factory()->unread()->create(['user_id' => $this->user->id]);

        Sanctum::actingAs($this->user);

        $this->putJson("/api/v1/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Notification marked as read',
                'data' => ['is_read' => true],
            ]);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_all_as_read(): void
    {
        Notification::factory()->unread()->count(3)->create(['user_id' => $this->user->id]);
        Notification::factory()->unread()->count(2)->create(['user_id' => User::factory()->create()->id]);

        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/notifications/read-all');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => ['marked' => 3],
            ]);

        $this->assertEquals(0, Notification::where('user_id', $this->user->id)->unread()->count());
    }

    public function test_user_cannot_read_other_users_notification(): void
    {
        $other = User::factory()->create();
        $notification = Notification::factory()->unread()->create(['user_id' => $other->id]);

        Sanctum::actingAs($this->user);

        $this->putJson("/api/v1/notifications/{$notification->id}/read")
            ->assertStatus(403);

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_user_can_delete_own_notification(): void
    {
        $notification = Notification::factory()->create(['user_id' => $this->user->id]);

        Sanctum::actingAs($this->user);

        $this->deleteJson("/api/v1/notifications/{$notification->id}")
            ->assertOk()
            ->assertJson(['message' => 'Notification deleted']);

        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_user_cannot_delete_other_users_notification(): void
    {
        $other = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $other->id]);

        Sanctum::actingAs($this->user);

        $this->deleteJson("/api/v1/notifications/{$notification->id}")
            ->assertStatus(403);
    }
}
