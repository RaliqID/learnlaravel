<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class FollowingSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['username' => 'followeruser']);
    }

    public function test_authenticated_user_can_follow_another_user(): void
    {
        $target = User::factory()->create(['username' => 'targetuser']);

        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/users/{$target->username}/follow")
            ->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'You are now following this user',
                'data' => ['following' => true, 'created' => true],
            ]);

        $this->assertDatabaseHas('follows', [
            'follower_id' => $this->user->id,
            'following_id' => $target->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_follow(): void
    {
        $target = User::factory()->create();

        $this->postJson("/api/v1/users/{$target->username}/follow")->assertStatus(401);
    }

    public function test_user_cannot_follow_themselves(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/users/{$this->user->username}/follow")
            ->assertStatus(422);

        $this->assertDatabaseMissing('follows', ['following_id' => $this->user->id]);
    }

    public function test_duplicate_follow_is_prevented(): void
    {
        $target = User::factory()->create();

        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/users/{$target->username}/follow")->assertOk();
        $this->postJson("/api/v1/users/{$target->username}/follow")
            ->assertOk()
            ->assertJsonPath('data.created', false)
            ->assertJsonPath('message', 'You are already following this user');

        $this->assertEquals(1, \App\Models\Follow::where('follower_id', $this->user->id)
            ->where('following_id', $target->id)->count());
    }

    public function test_authenticated_user_can_unfollow(): void
    {
        $target = User::factory()->create();

        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/users/{$target->username}/follow")->assertOk();

        $this->deleteJson("/api/v1/users/{$target->username}/follow")
            ->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'You have unfollowed this user',
                'data' => ['following' => false, 'removed' => true],
            ]);

        $this->assertDatabaseMissing('follows', [
            'follower_id' => $this->user->id,
            'following_id' => $target->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_unfollow(): void
    {
        $target = User::factory()->create();

        $this->deleteJson("/api/v1/users/{$target->username}/follow")->assertStatus(401);
    }

    public function test_unfollow_when_not_following_is_idempotent(): void
    {
        $target = User::factory()->create();

        Sanctum::actingAs($this->user);

        $this->deleteJson("/api/v1/users/{$target->username}/follow")
            ->assertOk()
            ->assertJsonPath('data.removed', false)
            ->assertJsonPath('message', 'You were not following this user');
    }

    public function test_followers_list_works(): void
    {
        $target = User::factory()->create(['username' => 'staruser']);
        $follower = User::factory()->create(['username' => 'fanone']);
        $follower2 = User::factory()->create(['username' => 'fantwo']);

        $this->postFollow($follower, $target);
        $this->postFollow($follower2, $target);

        $response = $this->getJson("/api/v1/users/{$target->username}/followers")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.pagination.total', 2);

        $usernames = collect($response->json('data'))->pluck('username')->sort()->values();

        $this->assertEquals(['fanone', 'fantwo'], $usernames->all());
    }

    public function test_following_list_works(): void
    {
        $following1 = User::factory()->create(['username' => 'heroone']);
        $following2 = User::factory()->create(['username' => 'herotwo']);

        $this->postFollow($this->user, $following1);
        $this->postFollow($this->user, $following2);

        $this->getJson("/api/v1/users/{$this->user->username}/following")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.pagination.total', 2);
    }

    public function test_followers_pagination_works(): void
    {
        $target = User::factory()->create(['username' => 'popularuser']);
        $followers = User::factory()->count(25)->create();
        foreach ($followers as $follower) {
            $this->postFollow($follower, $target);
        }

        $pageOne = $this->getJson("/api/v1/users/{$target->username}/followers?per_page=10&page=1")
            ->assertOk()
            ->json();

        $this->assertCount(10, $pageOne['data']);
        $this->assertEquals(25, $pageOne['meta']['pagination']['total']);
        $this->assertEquals(3, $pageOne['meta']['pagination']['last_page']);
    }

    public function test_list_returns_public_data_only(): void
    {
        $target = User::factory()->create();
        $this->postFollow($this->user, $target);

        $data = $this->getJson("/api/v1/users/{$target->username}/followers")
            ->assertOk()
            ->json('data.0');

        $this->assertArrayHasKey('username', $data);
        $this->assertArrayHasKey('display_name', $data);
        $this->assertArrayHasKey('avatar', $data);
        $this->assertArrayHasKey('bio', $data);
        $this->assertArrayNotHasKey('email', $data);
        $this->assertArrayNotHasKey('role', $data);
        $this->assertArrayNotHasKey('is_banned', $data);
        $this->assertArrayNotHasKey('password', $data);
    }

    public function test_is_following_status_works_for_authenticated_user(): void
    {
        $target = User::factory()->create(['username' => 'profileuser']);

        $this->postFollow($this->user, $target);

        Sanctum::actingAs($this->user);

        $this->getJson("/api/v1/users/{$target->username}")
            ->assertOk()
            ->assertJsonPath('data.is_following', true);
    }

    public function test_guest_profile_has_is_following_false(): void
    {
        $target = User::factory()->create();

        $this->getJson("/api/v1/users/{$target->username}")
            ->assertOk()
            ->assertJsonPath('data.is_following', false);
    }

    public function test_is_following_appears_in_followers_list_for_viewer(): void
    {
        $target = User::factory()->create(['username' => 'target']);
        $follower = User::factory()->create(['username' => 'follower']);

        $this->postFollow($follower, $target);
        $this->postFollow($this->user, $follower);

        Sanctum::actingAs($this->user);

        $this->getJson("/api/v1/users/{$target->username}/followers")
            ->assertOk()
            ->assertJsonPath('data.0.username', 'follower')
            ->assertJsonPath('data.0.is_following', true);
    }

    public function test_follow_notification_is_created(): void
    {
        $target = User::factory()->create();

        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/users/{$target->username}/follow")->assertOk();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $target->id,
            'type' => Notification::TYPE_USER_FOLLOWED,
        ]);
    }

    public function test_duplicate_follow_does_not_create_duplicate_notification(): void
    {
        $target = User::factory()->create();

        Sanctum::actingAs($this->user);

        $this->postJson("/api/v1/users/{$target->username}/follow")->assertOk();
        $this->postJson("/api/v1/users/{$target->username}/follow")->assertOk();

        $this->assertEquals(1, Notification::where('user_id', $target->id)
            ->where('type', Notification::TYPE_USER_FOLLOWED)->count());
    }

    public function test_invalid_target_user_returns_404(): void
    {
        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/users/doesnotexist/follow')->assertStatus(404);
        $this->getJson('/api/v1/users/doesnotexist/followers')->assertStatus(404);
    }

    public function test_deleted_target_user_returns_404(): void
    {
        $ghost = User::factory()->create(['username' => 'ghostuser']);
        $ghost->delete();

        Sanctum::actingAs($this->user);

        $this->postJson('/api/v1/users/ghostuser/follow')->assertStatus(404);
    }

    public function test_banned_user_cannot_follow(): void
    {
        $banned = User::factory()->banned()->create();
        $target = User::factory()->create();

        Sanctum::actingAs($banned);

        $this->postJson("/api/v1/users/{$target->username}/follow")
            ->assertStatus(403);

        $this->assertDatabaseMissing('follows', [
            'follower_id' => $banned->id,
            'following_id' => $target->id,
        ]);
    }

    private function postFollow(User $follower, User $target): void
    {
        Sanctum::actingAs($follower);

        $this->postJson("/api/v1/users/{$target->username}/follow")->assertOk();
    }
}
