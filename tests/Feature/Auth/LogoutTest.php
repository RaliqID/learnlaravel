<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Logout successful',
            ])
            ->assertJsonStructure([
                'meta' => ['timestamp'],
            ]);
    }

    public function test_logout_deletes_current_token(): void
    {
        $user = User::factory()->create();
        
        $token = $user->createToken('auth_token')->plainTextToken;

        $this->assertEquals(1, $user->tokens()->count());

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200);

        $this->assertEquals(0, $user->fresh()->tokens()->count());
    }

    public function test_logout_only_deletes_current_token_not_all_tokens(): void
    {
        $user = User::factory()->create();
        
        $token1 = $user->createToken('device1')->plainTextToken;
        $token2 = $user->createToken('device2')->plainTextToken;

        $this->assertEquals(2, $user->tokens()->count());

        $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200);

        $this->assertEquals(1, $user->fresh()->tokens()->count());
    }

    public function test_unauthenticated_user_cannot_logout(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    public function test_logout_with_invalid_token_fails(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid-token')
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    public function test_logout_with_expired_token_fails(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $user->tokens()->delete();

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(401);
    }

    public function test_logout_removes_token_from_database(): void
    {
        $user = User::factory()->create();
        
        $tokenResult = $user->createToken('auth_token');
        $token = $tokenResult->plainTextToken;
        $tokenId = $tokenResult->accessToken->id;

        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $tokenId,
            'tokenable_id' => $user->id,
        ]);

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenId,
        ]);
    }
}
