<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\Comment;
use App\Models\Follow;
use App\Models\Post;
use App\Models\PostVote;
use App\Models\Report;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AnalyticsSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
    }

    // ------------------------------------------------------------------
    // AUTHORIZATION
    // ------------------------------------------------------------------

    public function test_guest_gets_401(): void
    {
        $this->getJson('/api/v1/admin/analytics/overview')->assertStatus(401);
        $this->getJson('/api/v1/admin/analytics/users')->assertStatus(401);
        $this->getJson('/api/v1/admin/analytics/content')->assertStatus(401);
        $this->getJson('/api/v1/admin/analytics/moderation')->assertStatus(401);
    }

    public function test_regular_user_gets_403(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/admin/analytics/overview')->assertStatus(403);
    }

    public function test_moderator_gets_403(): void
    {
        $mod = User::factory()->moderator()->create();

        Sanctum::actingAs($mod);

        $this->getJson('/api/v1/admin/analytics/overview')->assertStatus(403);
    }

    public function test_admin_allowed(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/admin/analytics/overview')->assertOk();
    }

    // ------------------------------------------------------------------
    // OVERVIEW COUNTS
    // ------------------------------------------------------------------

    public function test_overview_counts_are_correct(): void
    {
        User::factory()->count(3)->create();
        Post::factory()->count(4)->published()->create(['user_id' => $this->admin->id]);
        $post = Post::factory()->published()->create(['user_id' => $this->admin->id]);
        Comment::factory()->count(2)->create(['post_id' => $post->id]);
        PostVote::create(['user_id' => $this->admin->id, 'post_id' => $post->id, 'vote_type' => 1]);
        Bookmark::create(['user_id' => $this->admin->id, 'post_id' => $post->id]);
        Follow::create(['follower_id' => $this->admin->id, 'following_id' => $post->user_id]);
        Report::factory()->create();

        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/admin/analytics/overview?period=all')
            ->assertOk()
            ->assertJsonPath('data.users', User::count())
            ->assertJsonPath('data.posts', Post::count())
            ->assertJsonPath('data.comments', Comment::count())
            ->assertJsonPath('data.post_votes', PostVote::count())
            ->assertJsonPath('data.bookmarks', Bookmark::count())
            ->assertJsonPath('data.follows', Follow::count())
            ->assertJsonPath('data.reports', Report::count());
    }

    public function test_overview_defaults_to_30d(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/admin/analytics/overview')
            ->assertOk()
            ->assertJsonPath('data.period', '30d');
    }

    // ------------------------------------------------------------------
    // USER ACTIVITY
    // ------------------------------------------------------------------

    public function test_user_activity_series_and_date_filtering(): void
    {
        User::factory()->count(2)->create(['created_at' => now()->subDays(5)]);
        User::factory()->create(['created_at' => now()->subDays(45)]);

        Post::factory()->create(['user_id' => $this->admin->id, 'created_at' => now()->subDays(2)]);

        Sanctum::actingAs($this->admin);

        // 7d excludes the 45-day-old user, includes the admin + 5-day-old ones
        $response = $this->getJson('/api/v1/admin/analytics/users?period=7d')
            ->assertOk();

        $this->assertEquals('7d', $response->json('data.period'));
        $usersTotal = collect($response->json('data.series.users'))->sum('total');
        $this->assertEquals(3, $usersTotal); // admin (today) + 2 users (5d ago)
    }

    public function test_user_activity_empty_period_returns_empty_series(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/admin/analytics/users?period=today')
            ->assertOk()
            ->assertJsonCount(1, 'data.series.users'); // admin created today
    }

    // ------------------------------------------------------------------
    // CONTENT PERFORMANCE
    // ------------------------------------------------------------------

    public function test_content_performance_ranks_correctly(): void
    {
        $postA = Post::factory()->published()->create(['view_count' => 100, 'vote_score' => 50, 'bookmark_count' => 20, 'comment_count' => 10]);
        Post::factory()->published()->create(['view_count' => 10, 'vote_score' => 5, 'bookmark_count' => 2, 'comment_count' => 1]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/admin/analytics/content?limit=1')
            ->assertOk();

        $this->assertEquals($postA->id, $response->json('data.most_viewed.0.id'));
        $this->assertEquals($postA->id, $response->json('data.most_voted.0.id'));
        $this->assertEquals($postA->id, $response->json('data.most_bookmarked.0.id'));
        $this->assertEquals($postA->id, $response->json('data.most_commented.0.id'));
        $this->assertEquals(110, $response->json('data.total_views'));
    }

    public function test_content_performance_excludes_draft_deleted(): void
    {
        Post::factory()->draft()->create(['view_count' => 999]);
        $deleted = Post::factory()->published()->create(['view_count' => 888]);
        $deleted->delete();
        Post::factory()->published()->create(['view_count' => 5]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/admin/analytics/content')
            ->assertOk();

        $this->assertEquals(5, $response->json('data.total_views'));
        $this->assertCount(1, $response->json('data.most_viewed'));
    }

    // ------------------------------------------------------------------
    // MODERATION METRICS
    // ------------------------------------------------------------------

    public function test_moderation_metrics_counts(): void
    {
        Report::factory()->create(['status' => 'pending']);
        Report::factory()->create(['status' => 'resolved']);
        Report::factory()->create(['status' => 'dismissed']);
        Report::factory()->create(['status' => 'reviewing']);

        $mod = User::factory()->moderator()->create();
        \App\Models\ModerationLog::create(['moderator_id' => $mod->id, 'action' => 'post:remove', 'target_type' => Post::class, 'target_id' => 1]);
        \App\Models\ModerationLog::create(['moderator_id' => $mod->id, 'action' => 'user:ban', 'target_type' => User::class, 'target_id' => 1]);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/admin/analytics/moderation?period=all')
            ->assertOk();

        $this->assertEquals(4, $response->json('data.reports.total'));
        $this->assertEquals(2, $response->json('data.reports.open'));
        $this->assertEquals(1, $response->json('data.reports.resolved'));
        $this->assertEquals(1, $response->json('data.reports.dismissed'));
        $this->assertEquals(2, $response->json('data.moderation_actions.total'));
    }

    // ------------------------------------------------------------------
    // VALIDATION / EDGE CASES
    // ------------------------------------------------------------------

    public function test_invalid_period_rejected(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/admin/analytics/overview?period=decade')
            ->assertStatus(422);
    }

    public function test_invalid_granularity_rejected(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/admin/analytics/users?granularity=year')
            ->assertStatus(422);
    }

    public function test_invalid_limit_rejected(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/admin/analytics/content?limit=999')
            ->assertStatus(422);
    }

    public function test_empty_database_returns_zero_counts(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/admin/analytics/overview?period=all')->assertOk();

        $this->assertEquals(1, $response->json('data.users')); // admin itself
        $this->assertEquals(0, $response->json('data.posts'));
        $this->assertEquals(0, $response->json('data.reports'));
    }
}
