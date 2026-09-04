<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DataSectorsIngestService extends NewsIngestService
{
    public function ingest(string $sourceName = 'DataSectors', string $feedUrl = '', ?string $defaultTopicSlug = null): array
    {
        $result = ['imported' => 0, 'duplicates' => 0, 'failed' => 0, 'errors' => []];

        $apiKey = (string) config('datasectors.api_key');
        if ($apiKey === '') {
            Log::error('[DataSectorsIngest] Missing API key');
            $result['failed']++;
            $result['errors'][] = 'Missing API key';
            return $result;
        }

        try {
            $response = Http::withHeaders(['X-API-Key' => $apiKey])
                ->timeout((int) config('datasectors.timeout', 30))
                ->acceptJson()
                ->get(rtrim((string) config('datasectors.base_url'), '/') . '/api/news', [
                    'maxResults' => (int) config('datasectors.max_results', 100),
                    'sortBy' => 'createdAt',
                    'sortOrder' => 'desc',
                ]);

            if (!$response->successful()) {
                Log::warning('[DataSectorsIngest] Fetch failed', ['status' => $response->status()]);
                $result['failed']++;
                $result['errors'][] = (string) "HTTP {$response->status()}";
                return $result;
            }

            if (!$response->json('success')) {
                Log::warning('[DataSectorsIngest] API returned success=false');
                $result['failed']++;
                $result['errors'][] = 'API error response';
                return $result;
            }

            $items = $response->json('data.data');
            if (!is_array($items)) {
                Log::warning('[DataSectorsIngest] Missing data.data array');
                $result['failed']++;
                $result['errors'][] = 'Invalid response shape';
                return $result;
            }

            $marketTypeTopics = (array) config('datasectors.market_type_topics', []);

            foreach ($items as $item) {
                try {
                    if (!is_array($item)) {
                        $result['failed']++;
                        continue;
                    }

                    $id = (string) ($item['id'] ?? '');
                    $title = trim((string) ($item['title'] ?? ''));
                    $body = (string) ($item['body'] ?? '');
                    $providerId = (string) ($item['providerId'] ?? '');
                    $marketType = (string) ($item['marketType'] ?? '');
                    $createdAt = (string) ($item['createdAt'] ?? '');

                    if ($id === '' || $title === '') {
                        $result['failed']++;
                        continue;
                    }

                    // Stable synthetic URL — deterministic from provider id,
                    // so canonical_url dedupe works across runs.
                    $canonical = $this->normalizeUrl('https://datasectors.com/news/' . md5($id));

                    $outcome = $this->upsertArticle([
                        'title' => $title,
                        'canonical_url' => $canonical,
                        // Keyword classification wins, marketType mapping second,
                        // tech-industry last.
                        'default_topic_slug' => $marketTypeTopics[$marketType] ?? 'tech-industry',
                        'source_name' => 'DataSectors — ' . Str::title(str_replace(['-', '_'], ' ', $providerId)),
                        'source_url' => null,
                        'published_at' => $createdAt !== '' ? Carbon::parse($createdAt) : now(),
                        'content' => Str::limit($body, 1000),
                        'content_html' => nl2br(e($body)),
                        'image_candidates' => [],
                    ]);

                    if ($outcome === 'duplicate') {
                        $result['duplicates']++;
                    } else {
                        $result['imported']++;
                    }
                } catch (\Throwable $e) {
                    Log::warning('[DataSectorsIngest] Item failed', ['error' => $e->getMessage()]);
                    $result['failed']++;
                    $result['errors'][] = (string) $e->getMessage();
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[DataSectorsIngest] Source failed', ['error' => $e->getMessage()]);
            $result['failed']++;
            $result['errors'][] = (string) $e->getMessage();
        }

        return $result;
    }
}
