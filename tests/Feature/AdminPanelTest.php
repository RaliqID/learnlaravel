<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $moderator;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->moderator = User::factory()->moderator()->create();
        $this->user = User::factory()->create(['username' => 'regularuser']);
    }

    // ------------------------------------------------------------------
    // AUTHORIZATION — ADMIN ONLY
    // ------------------------------------------------------------------

    public function test_guest_cannot_access_dashboard(): void
    {
        $this->getJson('/api/v1/admin/dashboard')->assertStatus(401);
    }

    public function test_regular_user_cannot_access_dashboard(): void
    {
        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/admin/dashboard')->assertStatus(403);
    }

    public function test_moderator_cannot_access_dashboard(): void
    {
        Sanctum::actingAs($this->moderator);

        $this->getJson('/api/v1/admin/dashboard')->assertStatus(403);
    }

    public function test_guest_cannot_access_users(): void
    {
        $this->getJson('/api/v1/admin/users')->assertStatus(401);
    }

    public function test_regular_user_cannot_access_users(): void
    {
        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/admin/users')->assertStatus(403);
    }

    public function test_moderator_cannot_access_users(): void
    {
        Sanctum::actingAs($this->moderator);

        $this->getJson('/api/v1/admin/users')->assertStatus(403);
    }

    public function test_guest_cannot_access_user_detail(): void
    {
        $this->getJson('/api/v1/admin/users/'.$this->user->username)->assertStatus(401);
    }

    public function test_guest_cannot_update_role(): void
    {
        $this->putJson('/api/v1/admin/users/'.$this->user->username.'/role', ['role' => 'moderator'])->assertStatus(401);
    }

    // ------------------------------------------------------------------
    // DASHBOARD
    // ------------------------------------------------------------------

    public function test_dashboard_returns_stats(): void
    {
        $uniquePost = Post::factory()->published()->create(['title' => 'Unique dashboard title for stats']);
        Post::factory()->count(1)->draft()->create();
        Post::factory()->count(1)->archived()->create();
        $postToRemove = Post::factory()->published()->create();
        $postToRemove->delete();
        Report::factory()->create(['status' => 'pending']);

        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/admin/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [
                    'users' => ['total', 'active', 'banned', 'verified'],
                    'posts' => ['total', 'published', 'draft', 'archived', 'removed'],
                    'comments' => ['total', 'active', 'removed'],
                    'reports' => ['total', 'open', 'resolved', 'dismissed'],
                ],
                'meta' => ['timestamp'],
            ]);

        $this->assertEquals(\App\Models\User::count(), $response->json('data.users.total'));
        $this->assertEquals(\App\Models\User::where('is_banned', true)->where(function ($q) { $q->whereNull('banned_until')->orWhere('banned_until', '>', now()); })->count(), $response->json('data.users.banned'));
        $this->assertEquals(\App\Models\Post::where('status', 'published')->whereNull('deleted_at')->count(), $response->json('data.posts.published'));
        $this->assertEquals(\App\Models\Post::where('status', 'draft')->whereNull('deleted_at')->count(), $response->json('data.posts.draft'));
        $this->assertEquals(\App\Models\Post::where('status', 'archived')->whereNull('deleted_at')->count(), $response->json('data.posts.archived'));
        $this->assertEquals(\App\Models\Post::onlyTrashed()->count(), $response->json('data.posts.removed'));
        $this->assertEquals(\App\Models\Comment::where('is_deleted', false)->whereNull('deleted_at')->count(), $response->json('data.comments.active'));
        $this->assertEquals(\App\Models\Comment::where(function ($q) { $q->where('is_deleted', true)->orWhereNotNull('deleted_at'); })->count(), $response->json('data.comments.removed'));
        $this->assertEquals(\App\Models\Report::where('status', 'pending')->count(), $response->json('data.reports.open'));
    }

    public function test_dashboard_returns_zero_on_empty_database(): void
    {
        Sanctum::actingAs($this->admin);

        $response = $this->getJson('/api/v1/admin/dashboard')->assertOk();

        // admin + moderator + regular user from setUp
        $this->assertEquals(3, $response->json('data.users.total'));
        $this->assertEquals(0, $response->json('data.users.banned'));
        $this->assertEquals(0, $response->json('data.posts.total'));
        $this->assertEquals(0, $response->json('data.reports.total'));
    }

    // ------------------------------------------------------------------
    // USER MANAGEMENT
    // ------------------------------------------------------------------

    public function test_admin_can_list_users(): void
    {
        User::factory()->count(5)->create();

        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/admin/users')
            ->assertOk()
            ->assertJsonCount(8, 'data') // admin + moderator + user + 5
            ->assertJsonPath('meta.pagination.total', 8);
    }

    public function test_user_list_pagination(): void
    {
        User::factory()->count(25)->create();

        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/admin/users?per_page=10&page=1')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.pagination.total', 28)
            ->assertJsonPath('meta.pagination.last_page', 3);
    }

    public function test_admin_users_list_does_not_expose_sensitive_fields(): void
    {
        Sanctum::actingAs($this->admin);

        $data = $this->getJson('/api/v1/admin/users')
            ->assertOk()
            ->json('data.0');

        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('email', $data);
        $this->assertArrayNotHasKey('remember_token', $data);
        $this->assertArrayNotHasKey('tokens', $data);
    }

    public function test_admin_can_search_user_by_username(): void
    {
        $target = User::factory()->create(['username' => 'uniquehandle']);

        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/admin/users?search=uniquehandle')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $target->id);
    }

    public function test_admin_can_filter_users_by_role(): void
    {
        // setUp creates 1 moderator already; add 2 users + 1 more moderator
        User::factory()->count(2)->create();
        User::factory()->moderator()->create();

        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/admin/users?role=moderator')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.role', 'moderator');
    }

    public function test_admin_can_filter_banned_users(): void
    {
        $banned = User::factory()->banned()->create();

        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/admin/users?banned=true')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $banned->id)
            ->assertJsonPath('data.0.is_banned', true);
    }

    public function test_admin_can_view_user_detail(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/admin/users/'.$this->user->username)
            ->assertOk()
            ->assertJsonPath('data.id', $this->user->id)
            ->assertJsonPath('data.username', $this->user->username);
    }

    public function test_admin_can_update_user_role(): void
    {
        Sanctum::actingAs($this->admin);

        $this->putJson('/api/v1/admin/users/'.$this->user->username.'/role', ['role' => 'moderator'])
            ->assertOk()
            ->assertJsonPath('data.role', 'moderator');

        $this->assertEquals('moderator', $this->user->fresh()->role);
    }

    public function test_admin_cannot_update_own_role(): void
    {
        Sanctum::actingAs($this->admin);

        $this->putJson('/api/v1/admin/users/'.$this->admin->username.'/role', ['role' => 'user'])
            ->assertStatus(403);
    }

    public function test_moderator_cannot_update_role(): void
    {
        Sanctum::actingAs($this->moderator);

        $this->putJson('/api/v1/admin/users/'.$this->user->username.'/role', ['role' => 'moderator'])
            ->assertStatus(403);
    }

    public function test_user_cannot_update_role(): void
    {
        Sanctum::actingAs($this->user);

        $this->putJson('/api/v1/admin/users/'.$this->admin->username.'/role', ['role' => 'user'])
            ->assertStatus(403);
    }

    public function test_soft_deleted_user_detail_returns_404(): void
    {
        $this->user->delete();

        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/admin/users/'.$this->user->username)->assertStatus(404);
    }
}
