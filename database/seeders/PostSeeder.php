<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use App\Services\HotScoreService;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()->limit(5)->get();

        if ($users->isEmpty()) {
            $users = collect([
                User::factory()->create(['username' => 'johndoe', 'email' => 'john@example.com']),
                User::factory()->create(['username' => 'janedoe', 'email' => 'jane@example.com']),
                User::factory()->create(['username' => 'bobsmith', 'email' => 'bob@example.com']),
            ]);
        }

        $topics = Topic::query()->get();

        if ($topics->isEmpty()) {
            $this->call(TopicSeeder::class);
            $topics = Topic::query()->get();
        }

        $hotScoreService = app(HotScoreService::class);

        $posts = Post::factory()
            ->count(80)
            ->create([
                'user_id' => fn () => $users->random()->id,
                'topic_id' => fn () => $topics->random()->id,
            ]);

        foreach ($posts as $index => $post) {
            $post->forceFill([
                'featured_at' => $index % 13 === 0 ? now()->subHours($index) : null,
                'featured_by' => $index % 13 === 0 ? $users->first()->id : null,
                'is_pinned' => $index % 17 === 0,
                'published_at' => now()->subHours($index + 1),
            ])->saveQuietly();

            $hotScoreService->recalculate($post->fresh());
        }
    }
}
