<?php

namespace App\Console\Commands;

use App\Services\DataSectorsIngestService;
use App\Services\NewsIngestService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class FetchNewsCommand extends Command
{
    protected $signature = 'news:fetch
                            {--provider=datasectors : datasectors or rss (default datasectors)}
                            {--source= : RSS only: fetch specific source name}';

    protected $description = 'Fetch news from DataSectors API (default) or RSS sources (manual fallback)';

    public function handle(DataSectorsIngestService $dataSectors, NewsIngestService $ingest): int
    {
        $provider = strtolower((string) $this->option('provider'));

        if ($provider === 'rss') {
            return $this->fetchRss($ingest);
        }

        $this->info('Fetching: DataSectors API');
        $result = $dataSectors->ingest('DataSectors');
        $this->table(['Source', 'Imported', 'Duplicates', 'Failed', 'Errors'], [[
            'DataSectors',
            $result['imported'],
            $result['duplicates'],
            $result['failed'],
            implode('; ', array_slice(array_map('strval', $result['errors']), 0, 3)),
        ]]);
        $this->info("Total imported: {$result['imported']}");
        Log::info('[FetchNews] DataSectors done', ['result' => $result]);
        return self::SUCCESS;
    }

    protected function fetchRss(NewsIngestService $ingest): int
    {
        $sources = config('news_sources', []);
        $filter = $this->option('source');
        if ($filter) {
            $sources = array_filter($sources, fn($s) => $s['name'] === $filter);
        }

        $results = [];
        foreach ($sources as $source) {
            if (!$source['enabled']) {
                $this->line("Skipping disabled: {$source['name']}");
                continue;
            }
            $this->info("Fetching: {$source['name']}");
            $result = $ingest->ingest($source['name'], $source['url'], $source['default_topic'] ?? null);
            $results[] = [
                'source' => $source['name'],
                'imported' => $result['imported'],
                'duplicates' => $result['duplicates'],
                'failed' => $result['failed'],
                'errors' => implode('; ', array_slice(array_map('strval', $result['errors']), 0, 3)),
            ];
            Log::info('[FetchNews] Source done', ['source' => $source['name'], 'result' => $result]);
        }

        $this->table(['Source', 'Imported', 'Duplicates', 'Failed', 'Errors'], $results);
        $total = array_reduce($results, fn($c, $r) => $c + $r['imported'], 0);
        $this->info("Total imported: {$total}");
        return self::SUCCESS;
    }
}
