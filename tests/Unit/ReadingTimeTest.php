<?php

namespace Tests\Unit;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingTimeTest extends TestCase
{
    use RefreshDatabase;

    public function test_reading_time_is_at_least_one_minute(): void
    {
        $post = Post::factory()->make(['content' => null]);

        $this->assertSame(1, $post->reading_time);
    }

    public function test_reading_time_for_200_words_is_one_minute(): void
    {
        $post = Post::factory()->make(['content' => implode(' ', array_fill(0, 200, 'word'))]);

        $this->assertSame(1, $post->reading_time);
    }

    public function test_reading_time_for_400_words_is_two_minutes(): void
    {
        $post = Post::factory()->make(['content' => implode(' ', array_fill(0, 400, 'word'))]);

        $this->assertSame(2, $post->reading_time);
    }

    public function test_reading_time_for_roughly_1000_words_is_five_minutes(): void
    {
        $post = Post::factory()->make(['content' => implode(' ', array_fill(0, 1000, 'word'))]);

        $this->assertSame(5, $post->reading_time);
    }

    public function test_markdown_syntax_is_ignored_in_word_count(): void
    {
        $content = implode(' ', array_fill(0, 400, '**word**'));
        $post = Post::factory()->make(['content' => $content]);

        $this->assertSame(2, $post->reading_time);
    }
}
