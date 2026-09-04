<?php

namespace Tests\Feature;

use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TopicSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_cannot_subscribe(): void
    {
        $topic = Topic::factory()->create();

        $this->postJson("/api/v1/topics/{$topic->id}/subscribe")
            ->assertStatus(401);
    }

    public function test_user_can_subscribe_to_topic(): void
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create(['subscriber_count' => 10]);

        Sanctum::actingAs($user);

        $response = $this->postJson("/api/v1/topics/{$topic->id}/subscribe");

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Subscribed to topic',
                'data' => [
                    'topic' => [
                        'subscriber_count' => 11,
                        'is_subscribed' => true,
                    ],
                ],
            ]);

        $this->assertDatabaseHas('topic_subscriptions', [
            'user_id' => $user->id,
            'topic_id' => $topic->id,
        ]);

        $this->assertDatabaseHas('topics', [
            'id' => $topic->id,
            'subscriber_count' => 11,
        ]);
    }

    public function test_double_subscribe_is_idempotent(): void
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create(['subscriber_count' => 5]);

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/topics/{$topic->id}/subscribe")->assertOk();
        $this->postJson("/api/v1/topics/{$topic->id}/subscribe")->assertOk();

        $this->assertEquals(6, $topic->fresh()->subscriber_count);
        $this->assertEquals(1, $topic->subscribers()->count());
    }

    public function test_user_can_unsubscribe_from_topic(): void
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create(['subscriber_count' => 20]);

        $topic->subscribers()->attach($user->id, ['created_at' => now()]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/topics/{$topic->id}/subscribe");

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Unsubscribed from topic',
                'data' => [
                    'topic' => [
                        'subscriber_count' => 19,
                        'is_subscribed' => false,
                    ],
                ],
            ]);

        $this->assertDatabaseMissing('topic_subscriptions', [
            'user_id' => $user->id,
            'topic_id' => $topic->id,
        ]);

        $this->assertDatabaseHas('topics', [
            'id' => $topic->id,
            'subscriber_count' => 19,
        ]);
    }

    public function test_unsubscribe_when_not_subscribed_is_idempotent(): void
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create(['subscriber_count' => 3]);

        Sanctum::actingAs($user);

        $response = $this->deleteJson("/api/v1/topics/{$topic->id}/subscribe");

        $response->assertOk()
            ->assertJson([
                'message' => 'You are not subscribed to this topic',
            ]);

        $this->assertEquals(3, $topic->fresh()->subscriber_count);
    }

    public function test_subscriber_count_never_goes_below_zero(): void
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create(['subscriber_count' => 0]);

        $topic->subscribers()->attach($user->id, ['created_at' => now()]);

        Sanctum::actingAs($user);

        $this->deleteJson("/api/v1/topics/{$topic->id}/subscribe")->assertOk();

        $this->assertGreaterThanOrEqual(0, $topic->fresh()->subscriber_count);
    }

    public function test_list_shows_subscription_status_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $subscribed = Topic::factory()->create(['name' => 'Subscribed Topic']);
        $other = Topic::factory()->create(['name' => 'Other Topic']);

        $subscribed->subscribers()->attach($user->id, ['created_at' => now()]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/topics');

        $response->assertOk();
        $topics = collect($response->json('data.topics'));

        $subscribedTopic = $topics->firstWhere('id', $subscribed->id);
        $otherTopic = $topics->firstWhere('id', $other->id);

        $this->assertTrue($subscribedTopic['is_subscribed']);
        $this->assertFalse($otherTopic['is_subscribed']);
    }

    public function test_show_returns_subscription_status_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create();

        $topic->subscribers()->attach($user->id, ['created_at' => now()]);

        Sanctum::actingAs($user);

        $response = $this->getJson("/api/v1/topics/{$topic->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'topic' => [
                        'is_subscribed' => true,
                    ],
                ],
            ]);
    }

    public function test_cannot_subscribe_to_deleted_topic(): void
    {
        $user = User::factory()->create();
        $topic = Topic::factory()->create();
        $topic->delete();

        Sanctum::actingAs($user);

        $this->postJson("/api/v1/topics/{$topic->id}/subscribe")
            ->assertStatus(404);
    }
}
