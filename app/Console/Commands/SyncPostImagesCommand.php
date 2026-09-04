<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\ImageResolver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncPostImagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'posts:sync-images {--force : Re-resolve all posts, even those already local}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing posts from remote to local images (or re-resolve all with --force)';

    /**
     * Execute the console command.
     */
    public function handle(ImageResolver $imageResolver): int
    {
        $force = (bool) $this->option('force');

        $this->info($force
            ? 'Re-resolving images for ALL posts (--force)...'
            : 'Syncing post images from remote to local...');

        $posts = Post::query()
            ->with('topic')
            ->where('status', 'published')
            ->get();

        $total = $posts->count();
        $processed = 0;
        $updated = 0;
        $replaced = 0;
        $skipped = 0;
        $failed = 0;

        $progressBar = $this->output->createProgressBar($total);
        $progressBar->start();

        foreach ($posts as $post) {
            $processed++;
            $wasLocal = str_starts_with((string) $post->image_url, '/storage/');

            try {
                // Without --force: skip posts already local (non-placeholder).
                // Placeholder means a previous run failed → retry.
                if (!$force && $wasLocal && !str_contains((string) $post->image_url, 'placeholder')) {
                    $skipped++;
                    $progressBar->advance();
                    continue;
                }

                $images = $imageResolver->resolveAndStore($post->title, $post->topic->name);

                $post->update([
                    'image_url' => $images['image_url'],
                    'thumbnail_url' => $images['thumbnail_url'],
                ]);

                if ($wasLocal) {
                    $replaced++;
                } else {
                    $updated++;
                }
            } catch (\Throwable $e) {
                $failed++;
                Log::error('[SyncPostImagesCommand] Post sync failed', [
                    'post_id' => $post->id,
                    'title' => $post->title,
                    'error' => $e->getMessage(),
                ]);
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->info('Sync complete:');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Processed', $processed],
                ['Updated (remote → local)', $updated],
                ['Replaced (re-resolved)', $replaced],
                ['Skipped (already local)', $skipped],
                ['Failed', $failed],
            ]
        );

        return self::SUCCESS;
    }
}