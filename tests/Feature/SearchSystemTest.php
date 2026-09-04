<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SearchSystemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * InnoDB FULLTEXT indexes built on an empty table do not match newly
     * inserted rows until the index is rebuilt with at least one document
     * (as happens once production data exists). Rebuild before searching so
     * the test database mirrors that state.
     */
    private function searchRequest(string $uri): \Illuminate\Testing\TestResponse
    {
        DB::statement('OPTIMIZE TABLE posts');

        return $this->getJson($uri);
    }

    public function test_guest_can_search_published_posts(): void
    {
        Post::factory()->published()->create([
            'title' => 'Laravel in action guide',
            'content' => 'A comprehensive walkthrough.',
        ]);

        $this->searchRequest('/api/v1/search?q=laravel')
            ->assertOk()
            ->assertJsonStructure([
                'status',
                'data' => [['id', 'title', 'slug', 'excerpt']],
                'meta' => ['query', 'sort', 'pagination', 'timestamp'],
            ])
            ->assertJson([
                'meta' => ['pagination' => ['total' => 1]],
            ]);
    }

    public function test_search_matches_post_title(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'Laravel deep dive',
            'content' => 'Nothing relevant here.',
        ]);
        Post::factory()->published()->create([
            'title' => 'Unrelated story',
            'content' => 'Nothing relevant here.',
        ]);

        $this->searchRequest('/api/v1/search?q=laravel')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $post->id);
    }

    public function test_search_matches_post_content(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'Unrelated story',
            'content' => 'This article discusses laravel framework internals in depth.',
        ]);

        $this->searchRequest('/api/v1/search?q=laravel')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $post->id);
    }

    public function test_search_returns_relevant_results_only(): void
    {
        Post::factory()->published()->create([
            'title' => 'Laravel tips',
            'content' => 'About laravel.',
        ]);
        Post::factory()->published()->count(3)->create([
            'title' => 'Politics report',
            'content' => 'Latest political news.',
        ]);

        $this->searchRequest('/api/v1/search?q=laravel')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.pagination.total', 1);
    }

    public function test_search_pagination_works(): void
    {
        Post::factory()->published()->count(25)->create([
            'title' => 'Laravel pagination test',
            'content' => 'Content about laravel pagination.',
        ]);

        $pageOne = $this->searchRequest('/api/v1/search?q=laravel&per_page=10&page=1')
            ->assertOk()
            ->json();

        $this->assertCount(10, $pageOne['data']);
        $this->assertEquals(25, $pageOne['meta']['pagination']['total']);
        $this->assertEquals(10, $pageOne['meta']['pagination']['per_page']);
        $this->assertEquals(3, $pageOne['meta']['pagination']['last_page']);

        $this->searchRequest('/api/v1/search?q=laravel&per_page=10&page=3')
            ->assertOk()
            ->assertJsonPath('meta.pagination.current_page', 3)
            ->assertJsonCount(5, 'data');
    }

    public function test_search_sort_newest(): void
    {
        Post::factory()->published()->create([
            'title' => 'Laravel first',
            'published_at' => now()->subDays(10),
        ]);
        $latest = Post::factory()->published()->create([
            'title' => 'Laravel second',
            'published_at' => now()->subDay(),
        ]);

        $this->searchRequest('/api/v1/search?q=laravel&sort=newest')
            ->assertOk()
            ->assertJsonPath('data.0.id', $latest->id);
    }

    public function test_search_sort_oldest(): void
    {
        $oldest = Post::factory()->published()->create([
            'title' => 'Laravel first',
            'published_at' => now()->subDays(10),
        ]);
        Post::factory()->published()->create([
            'title' => 'Laravel second',
            'published_at' => now()->subDay(),
        ]);

        $this->searchRequest('/api/v1/search?q=laravel&sort=oldest')
            ->assertOk()
            ->assertJsonPath('data.0.id', $oldest->id);
    }

    public function test_search_sort_popular(): void
    {
        $hot = Post::factory()->published()->create([
            'title' => 'Laravel trending',
            'hot_score' => 900,
        ]);
        Post::factory()->published()->create([
            'title' => 'Laravel quiet',
            'hot_score' => 10,
        ]);

        $this->searchRequest('/api/v1/search?q=laravel&sort=popular')
            ->assertOk()
            ->assertJsonPath('data.0.id', $hot->id);
    }

    public function test_search_filters_by_topic(): void
    {
        $topic = Topic::factory()->create();
        $otherTopic = Topic::factory()->create();

        $target = Post::factory()->published()->create([
            'title' => 'Laravel topic post',
            'topic_id' => $topic->id,
        ]);
        Post::factory()->published()->create([
            'title' => 'Laravel other topic post',
            'topic_id' => $otherTopic->id,
        ]);

        $this->searchRequest("/api/v1/search?q=laravel&topic_id={$topic->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $target->id);
    }

    public function test_search_filters_by_time_range(): void
    {
        Post::factory()->published()->create([
            'title' => 'Laravel old post',
            'published_at' => now()->subMonths(3),
        ]);
        $recent = Post::factory()->published()->create([
            'title' => 'Laravel recent post',
            'published_at' => now()->subDays(2),
        ]);

        $this->searchRequest('/api/v1/search?q=laravel&time=month')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $recent->id);
    }

    public function test_draft_posts_are_not_exposed(): void
    {
        Post::factory()->draft()->create([
            'title' => 'Laravel draft secret',
            'content' => 'Contains laravel content.',
        ]);
        Post::factory()->published()->create([
            'title' => 'Laravel published post',
            'content' => 'Contains laravel content.',
        ]);

        $this->searchRequest('/api/v1/search?q=laravel')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.pagination.total', 1);
    }

    public function test_archived_posts_are_not_exposed(): void
    {
        Post::factory()->archived()->create([
            'title' => 'Laravel archived post',
            'content' => 'Contains laravel content.',
        ]);
        Post::factory()->published()->create([
            'title' => 'Laravel published post',
            'content' => 'Contains laravel content.',
        ]);

        $this->searchRequest('/api/v1/search?q=laravel')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_unapproved_posts_are_not_exposed(): void
    {
        Post::factory()->unapproved()->create([
            'title' => 'Laravel unapproved post',
            'content' => 'Contains laravel content.',
        ]);
        Post::factory()->published()->create([
            'title' => 'Laravel approved post',
            'content' => 'Contains laravel content.',
        ]);

        $this->searchRequest('/api/v1/search?q=laravel')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_locked_posts_are_not_exposed(): void
    {
        Post::factory()->locked()->create([
            'title' => 'Laravel locked post',
            'content' => 'Contains laravel content.',
        ]);
        Post::factory()->published()->create([
            'title' => 'Laravel visible post',
            'content' => 'Contains laravel content.',
        ]);

        $this->searchRequest('/api/v1/search?q=laravel')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_deleted_posts_are_not_exposed(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'Laravel deleted post',
            'content' => 'Contains laravel content.',
        ]);
        $post->delete();

        $this->searchRequest('/api/v1/search?q=laravel')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_empty_search_result_state(): void
    {
        Post::factory()->published()->create([
            'title' => 'Laravel post',
            'content' => 'Contains laravel content.',
        ]);

        $this->searchRequest('/api/v1/search?q=zzzz-nomatch-keyword')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.pagination.total', 0);
    }

    public function test_invalid_query_is_rejected(): void
    {
        $this->searchRequest('/api/v1/search')->assertStatus(422);
        $this->searchRequest('/api/v1/search?q=')->assertStatus(422);
        $this->searchRequest('/api/v1/search?q=%20%20%20')->assertStatus(422);
        $this->searchRequest('/api/v1/search?q='.str_repeat('a', 101))->assertStatus(422);
        $this->searchRequest('/api/v1/search?q=laravel&sort=invalid')->assertStatus(422);
        $this->searchRequest('/api/v1/search?q=laravel&per_page=101')->assertStatus(422);
        $this->searchRequest('/api/v1/search?q=laravel&topic_id=999999')->assertStatus(422);
    }

    public function test_search_posts_endpoint_alias_works(): void
    {
        Post::factory()->published()->create([
            'title' => 'Laravel alias post',
            'content' => 'Contains laravel content.',
        ]);

        $this->searchRequest('/api/v1/search/posts?q=laravel')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_search_topics_returns_only_active(): void
    {
        $active = Topic::factory()->create(['name' => 'Laravel Tips', 'is_active' => true]);
        Topic::factory()->create(['name' => 'Laravel Hidden', 'is_active' => false]);

        $this->searchRequest('/api/v1/search/topics?q=laravel')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $active->id);
    }

    public function test_search_users_returns_matching_non_banned_users(): void
    {
        $user = User::factory()->create(['username' => 'laraveldev', 'display_name' => 'Laravel Dev']);
        User::factory()->create(['username' => 'bannedlaravel', 'display_name' => 'Banned', 'is_banned' => true]);

        $this->searchRequest('/api/v1/search/users?q=laravel')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $user->id);
    }
}
