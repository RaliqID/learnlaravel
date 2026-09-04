<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\HotScoreService;
use Illuminate\Console\Command;

class RecalculateHotScoresCommand extends Command
{
    protected $signature = 'posts:recalculate-hot-scores {--limit= : Limit number of posts to process}';

    protected $description = 'Recalculate hot scores for all published posts';

    public function handle(HotScoreService $hotScoreService): int
    {
        $query = Post::query()->published();

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $posts = $query->get();

        if ($posts->isEmpty()) {
            $this->info('No published posts found.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($posts->count());
        $bar->start();

        foreach ($posts as $post) {
            $hotScoreService->recalculate($post);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Recalculated hot scores for {$posts->count()} posts.");

        return self::SUCCESS;
    }
}
