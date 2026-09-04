<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\ModerationLog;
use App\Models\Notification;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ModerationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    // ------------------------------------------------------------------
    // REPORTING
    // ------------------------------------------------------------------

    public function test_authenticated_user_can_report_post(): void
    {
        $post = Post::factory()->published()->create();

        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/reports', [
            'reportable_type' => 'post',
            'reportable_id' => $post->id,
            'reason' => 'spam',
            'description' => 'This is spam content.',
        ])->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Report submitted successfully',
                'data' => ['status' => 'pending', 'reason' => 'spam'],
            ]);

        $this->assertDatabaseHas('reports', [
            'reporter_id' => $this->user->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
            'reason' => 'spam',
        ]);
    }

    public function test_authenticated_user_can_report_comment(): void
    {
        $post = Post::factory()->published()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/reports', [
            'reportable_type' => 'comment',
            'reportable_id' => $comment->id,
            'reason' => 'harassment',
        ])->assertStatus(201);

        $this->assertDatabaseHas('reports', [
            'reportable_type' => Comment::class,
            'reportable_id' => $comment->id,
            'reason' => 'harassment',
        ]);
    }

    public function test_guest_cannot_report(): void
    {
        $post = Post::factory()->published()->create();

        $this->postJson('/api/v1/reports', [
            'reportable_type' => 'post',
            'reportable_id' => $post->id,
            'reason' => 'spam',
        ])->assertStatus(401);
    }

    public function test_invalid_report_is_rejected(): void
    {
        $post = Post::factory()->published()->create();

        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/reports', ['reportable_type' => 'user', 'reportable_id' => $post->id, 'reason' => 'spam'])
            ->assertStatus(422);
        $this->postJson('/api/v1/reports', ['reportable_type' => 'post', 'reportable_id' => $post->id, 'reason' => 'invalid'])
            ->assertStatus(422);
        $this->postJson('/api/v1/reports', ['reportable_type' => 'post', 'reportable_id' => $post->id, 'reason' => 'spam', 'description' => str_repeat('a', 1001)])
            ->assertStatus(422);
        $this->postJson('/api/v1/reports', ['reportable_type' => 'post', 'reportable_id' => 999999, 'reason' => 'spam'])
            ->assertStatus(404);
    }

    public function test_duplicate_open_report_is_conflict(): void
    {
        $post = Post::factory()->published()->create();

        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/reports', ['reportable_type' => 'post', 'reportable_id' => $post->id, 'reason' => 'spam'])
            ->assertStatus(201);
        $this->postJson('/api/v1/reports', ['reportable_type' => 'post', 'reportable_id' => $post->id, 'reason' => 'nsfw'])
            ->assertStatus(409);

        $this->assertEquals(1, Report::where('reporter_id', $this->user->id)
            ->where('reportable_id', $post->id)->count());
    }

    public function test_report_of_removed_content_returns_404(): void
    {
        $post = Post::factory()->published()->create();
        $post->delete();

        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/reports', ['reportable_type' => 'post', 'reportable_id' => $post->id, 'reason' => 'spam'])
            ->assertStatus(404);
    }

    public function test_regular_user_cannot_manipulate_report_status(): void
    {
        $report = Report::factory()->pending()->create();

        Sanctum::actingAs($this->user);

        $this->putJson("/api/v1/moderation/reports/{$report->id}", ['status' => 'resolved'])
            ->assertStatus(403);
    }

    // ------------------------------------------------------------------
    // MODERATION QUEUE
    // ------------------------------------------------------------------

    public function test_moderator_can_view_queue(): void
    {
        $moderator = User::factory()->moderator()->create();
        Report::factory()->count(3)->create();

        Sanctum::actingAs($moderator);

        $this->getJson('/api/v1/moderation/reports')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.pagination.total', 3);
    }

    public function test_admin_can_view_queue(): void
    {
        $admin = User::factory()->admin()->create();
        Report::factory()->count(2)->create();

        Sanctum::actingAs($admin);

        $this->getJson('/api/v1/moderation/reports')->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_regular_user_cannot_view_queue(): void
    {
        Report::factory()->create();

        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/moderation/reports')->assertStatus(403);
    }

    public function test_guest_receives_401_for_queue(): void
    {
        $this->getJson('/api/v1/moderation/reports')->assertStatus(401);
    }

    public function test_queue_filters_by_status_and_type(): void
    {
        $moderator = User::factory()->moderator()->create();
        $post = Post::factory()->published()->create();
        Report::factory()->pending()->create(['reportable_type' => Post::class, 'reportable_id' => $post->id, 'reason' => 'spam']);
        Report::factory()->resolved()->create(['reportable_type' => Comment::class]);

        Sanctum::actingAs($moderator);

        $this->getJson('/api/v1/moderation/reports?status=pending')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'pending');

        $this->getJson('/api/v1/moderation/reports?type=post')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_moderator_can_view_report_detail(): void
    {
        $moderator = User::factory()->moderator()->create();
        $post = Post::factory()->published()->create();
        $report = Report::factory()->pending()->create(['reportable_type' => Post::class, 'reportable_id' => $post->id]);

        Sanctum::actingAs($moderator);

        $this->getJson("/api/v1/moderation/reports/{$report->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $report->id)
            ->assertJsonPath('data.reportable.id', $post->id);
    }

    public function test_regular_user_cannot_view_report_detail(): void
    {
        $report = Report::factory()->pending()->create();

        Sanctum::actingAs($this->user);

        $this->getJson("/api/v1/moderation/reports/{$report->id}")->assertStatus(403);
    }

    public function test_moderator_can_resolve_report(): void
    {
        $moderator = User::factory()->moderator()->create();
        $post = Post::factory()->published()->create();
        $report = Report::factory()->pending()->create([
            'reporter_id' => $this->user->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        Sanctum::actingAs($moderator);

        $this->putJson("/api/v1/moderation/reports/{$report->id}", [
            'status' => 'resolved',
            'resolution_note' => 'Removed as spam',
        ])->assertOk()
            ->assertJsonPath('data.status', 'resolved');

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'resolved',
            'resolved_by' => $moderator->id,
        ]);
        $this->assertNotNull($report->fresh()->resolved_at);
    }

    public function test_report_resolved_notification_is_sent_to_reporter(): void
    {
        $moderator = User::factory()->moderator()->create();
        $post = Post::factory()->published()->create();
        $report = Report::factory()->pending()->create([
            'reporter_id' => $this->user->id,
            'reportable_type' => Post::class,
            'reportable_id' => $post->id,
        ]);

        Sanctum::actingAs($moderator);

        $this->putJson("/api/v1/moderation/reports/{$report->id}", ['status' => 'dismissed'])
            ->assertOk();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => Notification::TYPE_REPORT_RESOLVED,
        ]);
    }

    public function test_resolving_already_resolved_report_returns_422(): void
    {
        $moderator = User::factory()->moderator()->create();
        $report = Report::factory()->resolved()->create();

        Sanctum::actingAs($moderator);

        $this->putJson("/api/v1/moderation/reports/{$report->id}", ['status' => 'resolved'])
            ->assertStatus(422);
    }

    // ------------------------------------------------------------------
    // CONTENT REMOVAL
    // ------------------------------------------------------------------

    public function test_moderator_can_remove_post(): void
    {
        $moderator = User::factory()->moderator()->create();
        $post = Post::factory()->published()->create();

        Sanctum::actingAs($moderator);

        $this->postJson("/api/v1/moderation/posts/{$post->id}/remove")
            ->assertOk()
            ->assertJson(['status' => 'success', 'message' => 'Post removed']);

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
        $this->assertDatabaseHas('moderation_logs', [
            'moderator_id' => $moderator->id,
            'action' => 'post:remove',
            'target_type' => Post::class,
            'target_id' => $post->id,
        ]);
    }

    public function test_moderator_can_remove_comment(): void
    {
        $moderator = User::factory()->moderator()->create();
        $post = Post::factory()->published()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        Sanctum::actingAs($moderator);

        $this->postJson("/api/v1/moderation/comments/{$comment->id}/remove")
            ->assertOk()
            ->assertJson(['message' => 'Comment removed']);

        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
        $this->assertTrue(\App\Models\Comment::withTrashed()->find($comment->id)->trashed());
    }

    public function test_regular_user_cannot_remove_content(): void
    {
        $post = Post::factory()->published()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/moderation/posts/{$post->id}/remove")->assertStatus(403);
        $this->postJson("/api/v1/moderation/comments/{$comment->id}/remove")->assertStatus(403);
    }

    public function test_removed_post_is_hidden_from_feed(): void
    {
        $moderator = User::factory()->moderator()->create();
        $post = Post::factory()->published()->create(['title' => 'Moderated away']);

        $this->getJson('/api/v1/feed?per_page=50')->assertOk()->assertJsonCount(1, 'data.posts');

        Sanctum::actingAs($moderator);
        $this->postJson("/api/v1/moderation/posts/{$post->id}/remove")->assertOk();

        $this->getJson('/api/v1/feed?per_page=50')
            ->assertOk()
            ->assertJsonCount(0, 'data.posts');
    }

    public function test_removed_post_is_hidden_from_search(): void
    {
        $moderator = User::factory()->moderator()->create();
        $post = Post::factory()->published()->create(['title' => 'Moderated searchable title']);

        DB::statement('OPTIMIZE TABLE posts');
        $this->getJson('/api/v1/search?q=moderated')->assertOk()->assertJsonCount(1, 'data');

        Sanctum::actingAs($moderator);
        $this->postJson("/api/v1/moderation/posts/{$post->id}/remove")->assertOk();

        DB::statement('OPTIMIZE TABLE posts');
        $this->getJson('/api/v1/search?q=moderated')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_removed_post_is_hidden_from_user_profile(): void
    {
        $moderator = User::factory()->moderator()->create();
        $owner = User::factory()->create(['username' => 'owneruser']);
        $post = Post::factory()->published()->create(['user_id' => $owner->id]);

        $this->getJson('/api/v1/users/owneruser/posts')->assertOk()->assertJsonCount(1, 'data');

        Sanctum::actingAs($moderator);
        $this->postJson("/api/v1/moderation/posts/{$post->id}/remove")->assertOk();

        $this->getJson('/api/v1/users/owneruser/posts')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_removed_comment_is_hidden_from_post_comments(): void
    {
        $moderator = User::factory()->moderator()->create();
        $post = Post::factory()->published()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);

        $this->getJson("/api/v1/posts/{$post->id}/comments")->assertOk()->assertJsonCount(1, 'data');

        Sanctum::actingAs($moderator);
        $this->postJson("/api/v1/moderation/comments/{$comment->id}/remove")->assertOk();

        $this->getJson("/api/v1/posts/{$post->id}/comments")->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_content_removed_notification_is_sent_to_owner(): void
    {
        $moderator = User::factory()->moderator()->create();
        $owner = User::factory()->create();
        $post = Post::factory()->published()->create(['user_id' => $owner->id]);

        Sanctum::actingAs($moderator);
        $this->postJson("/api/v1/moderation/posts/{$post->id}/remove")->assertOk();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $owner->id,
            'type' => Notification::TYPE_CONTENT_REMOVED,
        ]);
    }

    // ------------------------------------------------------------------
    // RESTORE
    // ------------------------------------------------------------------

    public function test_moderator_can_restore_post(): void
    {
        $moderator = User::factory()->moderator()->create();
        $post = Post::factory()->published()->create();
        $post->delete();

        Sanctum::actingAs($moderator);

        $this->postJson("/api/v1/moderation/posts/{$post->id}/restore")
            ->assertOk()
            ->assertJson(['message' => 'Post restored']);

        $this->assertNotSoftDeleted('posts', ['id' => $post->id]);
    }

    public function test_moderator_can_restore_comment(): void
    {
        $moderator = User::factory()->moderator()->create();
        $post = Post::factory()->published()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);
        $comment->delete();

        Sanctum::actingAs($moderator);

        $this->postJson("/api/v1/moderation/comments/{$comment->id}/restore")
            ->assertOk()
            ->assertJson(['message' => 'Comment restored']);

        $this->assertNotSoftDeleted('comments', ['id' => $comment->id]);
    }

    public function test_regular_user_cannot_restore_content(): void
    {
        $post = Post::factory()->published()->create();
        $post->delete();

        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/moderation/posts/{$post->id}/restore")->assertStatus(403);
    }

    public function test_restored_post_returns_to_feed(): void
    {
        $moderator = User::factory()->moderator()->create();
        $post = Post::factory()->published()->create(['title' => 'Back again']);

        Sanctum::actingAs($moderator);
        $this->postJson("/api/v1/moderation/posts/{$post->id}/remove")->assertOk();
        $this->postJson("/api/v1/moderation/posts/{$post->id}/restore")->assertOk();

        $this->getJson('/api/v1/feed?per_page=50')->assertOk()->assertJsonCount(1, 'data.posts');
    }

    public function test_restore_non_removed_content_returns_422(): void
    {
        $moderator = User::factory()->moderator()->create();
        $post = Post::factory()->published()->create();

        Sanctum::actingAs($moderator);

        $this->postJson("/api/v1/moderation/posts/{$post->id}/restore")->assertStatus(422);
    }

    // ------------------------------------------------------------------
    // USER BAN / UNBAN
    // ------------------------------------------------------------------

    public function test_moderator_can_ban_user(): void
    {
        $moderator = User::factory()->moderator()->create();
        $target = User::factory()->create();

        Sanctum::actingAs($moderator);

        $this->postJson("/api/v1/moderation/users/{$target->username}/ban", [
            'reason' => 'Violation of community guidelines',
            'duration_days' => 7,
        ])->assertOk()
            ->assertJson(['message' => 'User banned']);

        $target->refresh();
        $this->assertTrue($target->is_banned);
        $this->assertNotNull($target->banned_until);
        $this->assertEquals('Violation of community guidelines', $target->banned_reason);
    }

    public function test_regular_user_cannot_ban(): void
    {
        $target = User::factory()->create();

        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/moderation/users/{$target->username}/ban", ['reason' => 'spam'])
            ->assertStatus(403);
    }

    public function test_user_cannot_ban_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/moderation/users/{$admin->username}/ban", ['reason' => 'self ban'])
            ->assertStatus(403);
    }

    public function test_admin_cannot_ban_another_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/moderation/users/{$otherAdmin->username}/ban", ['reason' => 'no'])
            ->assertStatus(403);
    }

    public function test_banned_user_cannot_perform_restricted_actions(): void
    {
        $moderator = User::factory()->moderator()->create();
        $target = User::factory()->create();

        Sanctum::actingAs($moderator);
        $this->postJson("/api/v1/moderation/users/{$target->username}/ban", ['reason' => 'spam'])->assertOk();

        $target->refresh();
        Sanctum::actingAs($target);
        $this->postJson('/api/v1/posts', [
            'title' => 'Should not post',
            'content' => 'blocked',
            'topic_id' => \App\Models\Topic::factory()->create()->id,
            'post_type' => 'text',
            'status' => 'published',
        ])->assertStatus(403);
    }

    public function test_banned_user_authentication_behavior_is_correct(): void
    {
        $moderator = User::factory()->moderator()->create();
        $target = User::factory()->create(['password' => 'Password1!x']);

        Sanctum::actingAs($moderator);
        $this->postJson("/api/v1/moderation/users/{$target->username}/ban", ['reason' => 'spam'])->assertOk();

        $this->postJson('/api/v1/auth/login', ['email' => $target->email, 'password' => 'Password1!x'])
            ->assertStatus(403);
    }

    public function test_admin_can_unban_user(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->banned()->create();

        Sanctum::actingAs($admin);

        $this->postJson("/api/v1/moderation/users/{$target->username}/unban")
            ->assertOk()
            ->assertJson(['message' => 'User unbanned']);

        $target->refresh();
        $this->assertFalse($target->is_banned);
        $this->assertNull($target->banned_until);
        $this->assertNull($target->banned_reason);
    }

    public function test_regular_user_cannot_unban(): void
    {
        $target = User::factory()->banned()->create();

        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/moderation/users/{$target->username}/unban")->assertStatus(403);
    }

    public function test_user_banned_notification_is_created(): void
    {
        $moderator = User::factory()->moderator()->create();
        $target = User::factory()->create();

        Sanctum::actingAs($moderator);
        $this->postJson("/api/v1/moderation/users/{$target->username}/ban", ['reason' => 'spam'])->assertOk();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $target->id,
            'type' => Notification::TYPE_USER_BANNED,
        ]);
    }

    public function test_user_unbanned_notification_is_created(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->banned()->create();

        Sanctum::actingAs($admin);
        $this->postJson("/api/v1/moderation/users/{$target->username}/unban")->assertOk();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $target->id,
            'type' => Notification::TYPE_USER_UNBANNED,
        ]);
    }

    // ------------------------------------------------------------------
    // SECURITY
    // ------------------------------------------------------------------

    public function test_guest_cannot_access_any_moderation_endpoint(): void
    {
        $post = Post::factory()->published()->create();

        $this->postJson('/api/v1/reports', ['reportable_type' => 'post', 'reportable_id' => $post->id, 'reason' => 'spam'])
            ->assertStatus(401);
        $this->postJson("/api/v1/moderation/posts/{$post->id}/remove")->assertStatus(401);
        $this->getJson('/api/v1/moderation/reports')->assertStatus(401);
    }

    public function test_moderation_logs_are_recorded_for_actions(): void
    {
        $moderator = User::factory()->moderator()->create();
        $post = Post::factory()->published()->create();

        Sanctum::actingAs($moderator);
        $this->postJson("/api/v1/moderation/posts/{$post->id}/remove")->assertOk();
        $this->postJson("/api/v1/moderation/posts/{$post->id}/restore")->assertOk();

        $actions = ModerationLog::where('moderator_id', $moderator->id)->pluck('action')->sort()->values();

        $this->assertEquals(['post:remove', 'post:restore'], $actions->all());
    }
}
