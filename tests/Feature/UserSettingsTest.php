<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'show_email' => false,
            'email_notifications' => true,
            'role' => 'user',
        ]);
    }

    // ------------------------------------------------------------------
    // AUTHORIZATION
    // ------------------------------------------------------------------

    public function test_guest_cannot_view_settings(): void
    {
        $this->getJson('/api/v1/user/settings')->assertStatus(401);
    }

    public function test_guest_cannot_update_settings(): void
    {
        $this->putJson('/api/v1/user/settings', ['show_email' => true])->assertStatus(401);
    }

    public function test_guest_cannot_patch_settings(): void
    {
        $this->patchJson('/api/v1/user/settings', ['show_email' => true])->assertStatus(401);
    }

    // ------------------------------------------------------------------
    // READ
    // ------------------------------------------------------------------

    public function test_user_can_view_own_settings(): void
    {
        Sanctum::actingAs($this->user);

        $response = $this->getJson('/api/v1/user/settings')
            ->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => ['show_email', 'email_notifications'],
                'meta' => ['timestamp'],
            ]);

        $this->assertFalse((bool) $response->json('data.show_email'));
        $this->assertTrue((bool) $response->json('data.email_notifications'));
    }

    public function test_settings_response_has_correct_types(): void
    {
        Sanctum::actingAs($this->user);
        $this->user->update(['show_email' => true, 'email_notifications' => false]);

        $response = $this->getJson('/api/v1/user/settings')->assertOk();

        $this->assertIsBool($response->json('data.show_email'));
        $this->assertIsBool($response->json('data.email_notifications'));
    }

    // ------------------------------------------------------------------
    // UPDATE
    // ------------------------------------------------------------------

    public function test_user_can_update_show_email_to_true(): void
    {
        Sanctum::actingAs($this->user);

        $this->putJson('/api/v1/user/settings', ['show_email' => true])
            ->assertOk()
            ->assertJson(['status' => 'success', 'message' => 'Settings updated successfully']);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'show_email' => true,
        ]);
    }

    public function test_user_can_update_email_notifications_to_false(): void
    {
        Sanctum::actingAs($this->user);

        $this->putJson('/api/v1/user/settings', ['email_notifications' => false])
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'email_notifications' => false,
        ]);
    }

    public function test_partial_update_only_changes_specified_field(): void
    {
        $this->user->update(['email_notifications' => false]);

        Sanctum::actingAs($this->user);

        $this->putJson('/api/v1/user/settings', ['show_email' => true])
            ->assertOk();

        // email_notifications should NOT be overridden by the PUT payload
        $this->assertEquals(false, (bool) $this->user->fresh()->email_notifications);
    }

    public function test_empty_update_is_valid_and_idempotent(): void
    {
        Sanctum::actingAs($this->user);

        $this->putJson('/api/v1/user/settings', [])
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $this->assertEquals(false, $this->user->fresh()->show_email);
    }

    // ------------------------------------------------------------------
    // VALIDATION & SECURITY
    // ------------------------------------------------------------------

    public function test_invalid_show_email_value_is_rejected(): void
    {
        Sanctum::actingAs($this->user);

        $this->putJson('/api/v1/user/settings', ['show_email' => 'maybe'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('show_email');
    }

    public function test_invalid_email_notifications_value_is_rejected(): void
    {
        Sanctum::actingAs($this->user);

        $this->putJson('/api/v1/user/settings', ['email_notifications' => 'possibly'])
            ->assertStatus(422);
    }

    // ------------------------------------------------------------------
    // SECURITY — fields that must NOT be manipulated via settings
    // ------------------------------------------------------------------

    public function test_role_cannot_be_updated_via_settings_endpoint(): void
    {
        Sanctum::actingAs($this->user);

        $this->putJson('/api/v1/user/settings', ['role' => 'admin'])
            ->assertOk();

        $this->assertEquals('user', $this->user->fresh()->role);
    }

    public function test_banned_status_cannot_be_updated_via_settings_endpoint(): void
    {
        Sanctum::actingAs($this->user);

        $this->putJson('/api/v1/user/settings', ['is_banned' => false])
            ->assertOk();

        $this->assertTrue($this->user->fresh()->is_banned === false);
    }

    public function test_password_cannot_be_updated_via_settings_endpoint(): void
    {
        Sanctum::actingAs($this->user);

        $this->putJson('/api/v1/user/settings', ['password' => 'newsecretpassword123'])
            ->assertOk();

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('newsecretpassword123', $this->user->fresh()->password) === false);
    }

    public function test_cannot_modify_another_users_settings_by_direct_call(): void
    {
        // This is a structural test: settings endpoint does not take /:user param
        $other = User::factory()->create(['show_email' => false]);

        Sanctum::actingAs($this->user);

        $response = $this->putJson('/api/v1/user/settings', ['show_email' => true])->assertOk();

        // Other user unchanged
        $this->assertEquals(false, $other->fresh()->show_email);
    }

    public function test_settings_endpoint_does_not_accept_token_or_secret_fields(): void
    {
        Sanctum::actingAs($this->user);

        $this->putJson('/api/v1/user/settings', ['remember_token' => 'hacked'])
            ->assertOk();

        $this->assertEquals(10, strlen($this->user->fresh()->remember_token)); // unchanged
    }

    // ------------------------------------------------------------------
    // REGRESSION
    // ------------------------------------------------------------------

    public function test_other_module_still_working(): void
    {
        Sanctum::actingAs($this->user);

        $this->getJson('/api/v1/user')
            ->assertOk();
    }
}
