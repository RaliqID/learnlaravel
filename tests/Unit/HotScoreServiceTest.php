<?php

namespace Tests\Unit;

use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use App\Services\HotScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HotScoreServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_score_uses_weighted_engagement_metrics(): void
    {
        $post = Post::factory()->create([
            'view_count' => 100,
            'comment_count' => 10,
            'upvote_count' => 20,
            'published_at' => now(),
        ]);

        $score = app(HotScoreService::class)->calculate($post);

        // views(100) + comments(10*3=30) + likes(20*2=40) - decay(~1) = ~169
        $this->assertGreaterThan(160, $score);
        $this->assertLessThan(175, $score);
    }

    public function test_fresh_post_scores_higher_than_old_post_with_same_engagement(): void
    {
        $fresh = Post::factory()->create([
            'view_count' => 100,
            'comment_count' => 10,
            'upvote_count' => 20,
            'published_at' => now()->subHour(),
        ]);

        $old = Post::factory()->create([
            'view_count' => 100,
            'comment_count' => 10,
            'upvote_count' => 20,
            'published_at' => now()->subDays(10),
        ]);

        $service = app(HotScoreService::class);

        $this->assertGreaterThan(
            $service->calculate($old),
            $service->calculate($fresh)
        );
    }

    public function test_score_never_negative(): void
    {
        $post = Post::factory()->create([
            'view_count' => 0,
            'comment_count' => 0,
            'upvote_count' => 0,
            'published_at' => now()->subYear(),
        ]);

        $score = app(HotScoreService::class)->calculate($post);

        $this->assertGreaterThanOrEqual(0, $score);
    }

    public function test_recalculate_persists_hot_score(): void
    {
        $post = Post::factory()->create([
            'view_count' => 50,
            'comment_count' => 5,
            'upvote_count' => 10,
            'published_at' => now(),
        ]);

        $score = app(HotScoreService::class)->recalculate($post);

        $this->assertEquals($score, (float) $post->fresh()->hot_score);
        $this->assertGreaterThan(0, $score);
    }
}
