<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'username' => 'johndoe',
            'display_name' => 'John Doe',
            'bio' => 'A curious reader.',
            'karma_score' => 42,
        ]);
    }

    public function test_guest_can_view_public_profile(): void
    {
        $this->getJson("/api/v1/users/{$this->user->username}")
            ->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $this->user->id,
                    'username' => 'johndoe',
                    'display_name' => 'John Doe',
                    'bio' => 'A curious reader.',
                    'karma_score' => 42,
                ],
            ]);
    }

    public function test_profile_returns_expected_public_fields(): void
    {
        $response = $this->getJson("/api/v1/users/{$this->user->username}");

        $response->assertOk()->assertJsonStructure([
            'status',
            'data' => [
                'id', 'username', 'display_name', 'avatar', 'bio', 'website', 'location',
                'karma_score', 'post_count', 'comment_count', 'is_verified', 'created_at',
            ],
            'meta' => ['timestamp'],
        ]);
    }

    public function test_private_fields_are_not_exposed(): void
    {
        $response = $this->getJson("/api/v1/users/{$this->user->username}");

        $response->assertOk();
        $this->assertArrayNotHasKey('email', $response->json('data'));
        $this->assertArrayNotHasKey('role', $response->json('data'));
        $this->assertArrayNotHasKey('is_banned', $response->json('data'));
        $this->assertArrayNotHasKey('password', $response->json('data'));
        $this->assertArrayNotHasKey('banned_until', $response->json('data'));
    }

    public function test_email_appears_when_user_opts_in(): void
    {
        $this->user->update(['show_email' => true]);

        $this->getJson("/api/v1/users/{$this->user->username}")
            ->assertOk()
            ->assertJsonPath('data.email', $this->user->email);
    }

    public function test_user_can_view_own_profile(): void
    {
        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/user')
            ->assertOk()
            ->assertJsonPath('data.id', $this->user->id)
            ->assertJsonPath('data.username', 'johndoe')
            ->assertJsonPath('data.email', $this->user->email);
    }

    public function test_authenticated_user_can_update_own_profile(): void
    {
        Sanctum::actingAs($this->user);

        $this->putJson("/api/v1/users/{$this->user->username}", [
            'display_name' => 'Jane Doe',
            'bio' => 'Updated bio.',
            'location' => 'Jakarta',
        ])->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'data' => ['display_name' => 'Jane Doe', 'bio' => 'Updated bio.', 'location' => 'Jakarta'],
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'display_name' => 'Jane Doe',
            'bio' => 'Updated bio.',
        ]);
    }

    public function test_user_cannot_update_another_users_profile(): void
    {
        $other = User::factory()->create();

        Sanctum::actingAs($this->user);

        $this->putJson("/api/v1/users/{$other->username}", [
            'display_name' => 'Hacked',
        ])->assertStatus(403);

        $this->assertDatabaseHas('users', [
            'id' => $other->id,
            'display_name' => $other->display_name,
        ]);
    }

    public function test_guest_cannot_update_profile(): void
    {
        $this->putJson("/api/v1/users/{$this->user->username}", [
            'display_name' => 'Hacked',
        ])->assertStatus(401);
    }

    public function test_username_validation(): void
    {
        Sanctum::actingAs($this->user);

        $this->putJson("/api/v1/users/{$this->user->username}", ['username' => 'ab'])
            ->assertStatus(422);
        $this->putJson("/api/v1/users/{$this->user->username}", ['username' => 'invalid name!'])
            ->assertStatus(422);
        $this->putJson("/api/v1/users/{$this->user->username}", ['username' => str_repeat('a', 51)])
            ->assertStatus(422);
    }

    public function test_duplicate_username_rejected(): void
    {
        $other = User::factory()->create(['username' => 'takenuser']);

        Sanctum::actingAs($this->user);

        $this->putJson("/api/v1/users/{$this->user->username}", ['username' => 'takenuser'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('username');
    }

    public function test_display_name_validation(): void
    {
        Sanctum::actingAs($this->user);

        $this->putJson("/api/v1/users/{$this->user->username}", ['display_name' => str_repeat('a', 101)])
            ->assertStatus(422);
    }

    public function test_bio_validation(): void
    {
        Sanctum::actingAs($this->user);

        $this->putJson("/api/v1/users/{$this->user->username}", ['bio' => str_repeat('a', 501)])
            ->assertStatus(422);
    }

    public function test_user_can_update_username_without_affecting_route(): void
    {
        Sanctum::actingAs($this->user);

        $this->putJson("/api/v1/users/{$this->user->username}", ['username' => 'newhandle'])
            ->assertOk()
            ->assertJsonPath('data.username', 'newhandle');

        $this->getJson('/api/v1/users/newhandle')->assertOk();
    }

    public function test_user_posts_pagination(): void
    {
        Post::factory()->published()->count(25)->create(['user_id' => $this->user->id]);

        $pageOne = $this->getJson("/api/v1/users/{$this->user->username}/posts?per_page=10&page=1")
            ->assertOk()
            ->json();

        $this->assertCount(10, $pageOne['data']);
        $this->assertEquals(25, $pageOne['meta']['pagination']['total']);
        $this->assertEquals(3, $pageOne['meta']['pagination']['last_page']);

        $this->getJson("/api/v1/users/{$this->user->username}/posts?per_page=10&page=3")
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function test_only_public_posts_appear(): void
    {
        Post::factory()->published()->create(['user_id' => $this->user->id, 'title' => 'Public post']);
        Post::factory()->draft()->create(['user_id' => $this->user->id, 'title' => 'Draft post']);
        Post::factory()->archived()->create(['user_id' => $this->user->id, 'title' => 'Archived post']);
        Post::factory()->unapproved()->create(['user_id' => $this->user->id, 'title' => 'Unapproved post']);
        Post::factory()->locked()->create(['user_id' => $this->user->id, 'title' => 'Locked post']);

        $this->getJson("/api/v1/users/{$this->user->username}/posts")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Public post');
    }

    public function test_deleted_posts_do_not_appear(): void
    {
        $deleted = Post::factory()->published()->create(['user_id' => $this->user->id]);
        $deleted->delete();
        Post::factory()->published()->create(['user_id' => $this->user->id]);

        $this->getJson("/api/v1/users/{$this->user->username}/posts")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_user_comments_pagination(): void
    {
        $post = Post::factory()->published()->create();
        Comment::factory()->count(25)->create(['user_id' => $this->user->id, 'post_id' => $post->id]);

        $pageOne = $this->getJson("/api/v1/users/{$this->user->username}/comments?per_page=10&page=1")
            ->assertOk()
            ->json();

        $this->assertCount(10, $pageOne['data']);
        $this->assertEquals(25, $pageOne['meta']['pagination']['total']);
    }

    public function test_deleted_hidden_comments_do_not_leak(): void
    {
        $post = Post::factory()->published()->create();
        Comment::factory()->create(['user_id' => $this->user->id, 'post_id' => $post->id, 'is_deleted' => true]);
        Comment::factory()->create(['user_id' => $this->user->id, 'post_id' => $post->id, 'is_deleted' => false]);

        $this->getJson("/api/v1/users/{$this->user->username}/comments")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_comments_on_unpublished_posts_do_not_leak(): void
    {
        $draftPost = Post::factory()->draft()->create();
        Comment::factory()->create(['user_id' => $this->user->id, 'post_id' => $draftPost->id]);

        $this->getJson("/api/v1/users/{$this->user->username}/comments")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_profile_statistics_are_correct(): void
    {
        $post = Post::factory()->published()->create(['user_id' => $this->user->id]);
        Post::factory()->published()->create(['user_id' => $this->user->id]);
        Post::factory()->draft()->create(['user_id' => $this->user->id]);
        Comment::factory()->count(3)->create(['user_id' => $this->user->id, 'post_id' => $post->id]);

        $this->getJson("/api/v1/users/{$this->user->username}")
            ->assertOk()
            ->assertJsonPath('data.post_count', 2)
            ->assertJsonPath('data.comment_count', 3);
    }

    public function test_unknown_user_returns_404(): void
    {
        $this->getJson('/api/v1/users/doesnotexist')->assertStatus(404);
    }

    public function test_deleted_user_returns_404(): void
    {
        $user = User::factory()->create(['username' => 'ghostuser']);
        $user->delete();

        $this->getJson('/api/v1/users/ghostuser')->assertStatus(404);
    }
}
