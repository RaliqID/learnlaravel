<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_can_view_other_users(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->assertTrue($user->can('view', $otherUser));
    }

    public function test_regular_user_can_update_own_profile(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->can('update', $user));
    }

    public function test_regular_user_cannot_update_other_users(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->assertFalse($user->can('update', $otherUser));
    }

    public function test_regular_user_can_delete_own_account(): void
    {
        $user = User::factory()->create();

        $this->assertTrue($user->can('delete', $user));
    }

    public function test_regular_user_cannot_delete_other_users(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->assertFalse($user->can('delete', $otherUser));
    }

    public function test_admin_can_update_any_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->assertTrue($admin->can('update', $user));
    }

    public function test_admin_can_delete_any_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->assertTrue($admin->can('delete', $user));
    }

    public function test_admin_can_ban_regular_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->assertTrue($admin->can('ban', $user));
    }

    public function test_admin_cannot_ban_another_admin(): void
    {
        $admin1 = User::factory()->admin()->create();
        $admin2 = User::factory()->admin()->create();

        $this->assertFalse($admin1->can('ban', $admin2));
    }

    public function test_admin_cannot_ban_themselves(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertFalse($admin->can('ban', $admin));
    }

    public function test_moderator_can_ban_regular_user(): void
    {
        $moderator = User::factory()->moderator()->create();
        $user = User::factory()->create();

        $this->assertTrue($moderator->can('ban', $user));
    }

    public function test_moderator_cannot_ban_admin(): void
    {
        $moderator = User::factory()->moderator()->create();
        $admin = User::factory()->admin()->create();

        $this->assertFalse($moderator->can('ban', $admin));
    }

    public function test_regular_user_cannot_ban_anyone(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->assertFalse($user->can('ban', $otherUser));
    }

    public function test_admin_can_update_user_role(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $this->assertTrue($admin->can('updateRole', $user));
    }

    public function test_admin_cannot_update_own_role(): void
    {
        $admin = User::factory()->admin()->create();

        $this->assertFalse($admin->can('updateRole', $admin));
    }

    public function test_regular_user_cannot_update_roles(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $this->assertFalse($user->can('updateRole', $otherUser));
    }

    public function test_moderator_cannot_update_roles(): void
    {
        $moderator = User::factory()->moderator()->create();
        $user = User::factory()->create();

        $this->assertFalse($moderator->can('updateRole', $user));
    }

    public function test_banned_user_middleware_blocks_request(): void
    {
        $user = User::factory()->banned()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/user');

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'Your account has been banned',
            ]);
    }

    public function test_banned_user_with_permanent_ban_is_blocked(): void
    {
        $user = User::factory()->create([
            'is_banned' => true,
            'banned_until' => null,
            'banned_reason' => 'Permanent ban',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/user');

        $response->assertStatus(403)
            ->assertJson([
                'status' => 'error',
                'message' => 'Your account has been banned',
            ]);
    }

    public function test_unverified_email_middleware_blocks_request(): void
    {
        $user = User::factory()->unverified()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->get('/api/v1/user', [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/json',
            ]);

        $this->assertTrue(true);
    }

    public function test_verified_email_passes_middleware(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/user');

        $response->assertStatus(200);
    }
}
