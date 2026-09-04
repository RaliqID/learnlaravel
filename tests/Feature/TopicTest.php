<?php

namespace Tests\Feature;

use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TopicTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_list_active_topics(): void
    {
        Topic::factory()->count(5)->create();

        $response = $this->getJson('/api/v1/topics');

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [
                    'topics' => [
                        '*' => ['id', 'name', 'slug', 'subscriber_count', 'post_count'],
                    ],
                ],
                'meta' => ['pagination', 'timestamp'],
            ]);

        $this->assertCount(5, $response->json('data.topics'));
    }

    public function test_guests_cannot_see_inactive_topics(): void
    {
        Topic::factory()->count(2)->create();
        Topic::factory()->inactive()->create();

        $response = $this->getJson('/api/v1/topics');

        $response->assertOk();
        $this->assertCount(2, $response->json('data.topics'));
    }

    public function test_admin_can_filter_inactive_topics(): void
    {
        $admin = User::factory()->admin()->create();
        $inactive = Topic::factory()->inactive()->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/v1/topics?is_active=false');

        $response->assertOk();
        $this->assertContains($inactive->id, collect($response->json('data.topics'))->pluck('id'));
    }

    public function test_topics_list_sorted_by_popularity(): void
    {
        Topic::factory()->create(['name' => 'Low', 'subscriber_count' => 5]);
        $top = Topic::factory()->create(['name' => 'High', 'subscriber_count' => 900]);

        $response = $this->getJson('/api/v1/topics');

        $response->assertOk();
        $this->assertEquals($top->name, $response->json('data.topics.0.name'));
    }

    public function test_guest_can_view_topic_detail(): void
    {
        $topic = Topic::factory()->create();

        $response = $this->getJson("/api/v1/topics/{$topic->id}");

        $response->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [
                    'topic' => ['id', 'name', 'slug', 'description', 'is_subscribed'],
                ],
                'meta' => ['timestamp'],
            ])
            ->assertJson([
                'data' => [
                    'topic' => [
                        'name' => $topic->name,
                        'is_subscribed' => false,
                    ],
                ],
            ]);
    }

    public function test_topic_detail_returns_404_for_missing_topic(): void
    {
        $response = $this->getJson('/api/v1/topics/99999');

        $response->assertStatus(404);
    }

    public function test_guest_cannot_create_topic(): void
    {
        $response = $this->postJson('/api/v1/topics', [
            'name' => 'New Topic',
        ]);

        $response->assertStatus(401);
    }

    public function test_regular_user_cannot_create_topic(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/topics', [
            'name' => 'New Topic',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_create_topic(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/topics', [
            'name' => 'Web Development',
            'description' => 'Semua tentang web dev',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'topic' => [
                        'name' => 'Web Development',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('topics', [
            'name' => 'Web Development',
            'slug' => 'web-development',
        ]);
    }

    public function test_topic_name_must_be_unique(): void
    {
        Topic::factory()->create(['name' => 'Laravel']);
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/topics', [
            'name' => 'Laravel',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_topic_name_required_and_min_length(): void
    {
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $this->postJson('/api/v1/topics', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);

        $this->postJson('/api/v1/topics', ['name' => 'ab'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_regular_user_cannot_update_topic(): void
    {
        $topic = Topic::factory()->create();
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/v1/topics/{$topic->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_update_topic(): void
    {
        $topic = Topic::factory()->create(['name' => 'Original']);
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $response = $this->putJson("/api/v1/topics/{$topic->id}", [
            'name' => 'Updated Name',
            'description' => 'Deskripsi baru',
        ]);

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'topic' => [
                        'name' => 'Updated Name',
                        'description' => 'Deskripsi baru',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('topics', [
            'id' => $topic->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_regular_user_cannot_delete_topic(): void
    {
        $topic = Topic::factory()->create();
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->deleteJson("/api/v1/topics/{$topic->id}")
            ->assertStatus(403);
    }

    public function test_admin_can_delete_topic(): void
    {
        $topic = Topic::factory()->create();
        $admin = User::factory()->admin()->create();

        Sanctum::actingAs($admin);

        $response = $this->deleteJson("/api/v1/topics/{$topic->id}");

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'message' => 'Topic deleted successfully',
            ]);

        $this->assertSoftDeleted('topics', ['id' => $topic->id]);

        $this->getJson("/api/v1/topics/{$topic->id}")
            ->assertStatus(404);
    }

    public function test_topic_list_rejects_invalid_sort(): void
    {
        $response = $this->getJson('/api/v1/topics?sort=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sort']);
    }
}
