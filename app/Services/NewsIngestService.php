<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class NewsIngestService
{
    protected ImageResolver $imageResolver;
    protected \App\Services\ImageService $imageService;

    public function __construct(ImageResolver $imageResolver, \App\Services\ImageService $imageService)
    {
        $this->imageResolver = $imageResolver;
        $this->imageService = $imageService;
    }

    public function ingest(string $sourceName, string $feedUrl, ?string $defaultTopicSlug = null): array
    {
        $result = ['imported' => 0, 'duplicates' => 0, 'failed' => 0, 'errors' => []];

        try {
            $response = Http::timeout(30)->get($feedUrl);
            if (!$response->successful()) {
                Log::warning('[NewsIngest] Fetch failed', ['source' => $sourceName, 'status' => $response->status()]);
                $result['failed']++;
                $result['errors'][] = (string) "HTTP {$response->status()}";
                return $result;
            }

            $xml = simplexml_load_string($response->body());
            if ($xml === false) {
                Log::warning('[NewsIngest] Parse failed', ['source' => $sourceName]);
                $result['failed']++;
                $result['errors'][] = (string) 'Invalid XML';
                return $result;
            }

            $items = $xml->channel->item ?? [];
            foreach ($items as $item) {
                try {
                    $title = (string) $item->title;
                    $link = (string) $item->link;
                    $description = (string) ($item->description ?? $item->children('content', true)->encoded ?? '');
                    $pubDate = isset($item->pubDate) ? Carbon::parse((string) $item->pubDate) : now();

                    if (empty($title) || empty($link)) {
                        $result['failed']++;
                        continue;
                    }

                    $outcome = $this->upsertArticle([
                        'title' => $title,
                        'canonical_url' => $this->normalizeUrl($link),
                        'default_topic_slug' => $defaultTopicSlug,
                        'source_name' => $sourceName,
                        'source_url' => $link,
                        'published_at' => $pubDate,
                        'content' => Str::limit(strip_tags($description), 1000),
                        'content_html' => $description,
                        'image_candidates' => $this->extractImageCandidates($item),
                    ]);

                    if ($outcome === 'duplicate') {
                        $result['duplicates']++;
                    } else {
                        $result['imported']++;
                    }
                } catch (\Throwable $e) {
                    Log::warning('[NewsIngest] Item failed', ['source' => $sourceName, 'error' => $e->getMessage()]);
                    $result['failed']++;
                    $result['errors'][] = (string) $e->getMessage();
                }
            }
        } catch (\Throwable $e) {
            Log::warning('[NewsIngest] Source failed', ['source' => $sourceName, 'error' => $e->getMessage()]);
            $result['failed']++;
            $result['errors'][] = (string) $e->getMessage();
        }

        return $result;
    }

    /**
     * Upsert one normalized article. Dedupes by canonical URL then normalized
     * title, resolves topic/image/user, creates the Post and recalcs hot score.
     * Returns 'imported' or 'duplicate'.
     */
    protected function upsertArticle(array $attrs): string
    {
        $title = (string) ($attrs['title'] ?? '');
        $canonical = (string) ($attrs['canonical_url'] ?? '');

        // URL dedupe
        if ($canonical !== '' && Post::where('canonical_url', $canonical)->exists()) {
            return 'duplicate';
        }

        // Title dedupe
        $normalizedTitle = preg_replace('/[^a-z0-9]/', '', strtolower($title));
        if ($normalizedTitle !== '' && Post::whereRaw('LOWER(REPLACE(REPLACE(title, " ", ""), "-", "")) = ?', [$normalizedTitle])->exists()) {
            return 'duplicate';
        }

        // Topic
        $topicSlug = $this->classifyTopic($title) ?? $attrs['default_topic_slug'] ?? 'technology';
        $topic = Topic::where('slug', $topicSlug)->first();
        if (!$topic) {
            $topic = Topic::where('slug', 'technology')->first();
        }
        if (!$topic) {
            $topic = Topic::first();
        }

        // Image
        $images = $this->resolveImageFromCandidates(
            array_values(array_filter((array) ($attrs['image_candidates'] ?? []), 'is_string')),
            $title,
            $topic?->name ?? 'Technology'
        );
        $imageUrl = $images['image_url'] ?? '/storage/images/placeholder.svg';
        $thumbnailUrl = $images['thumbnail_url'] ?? '/storage/images/placeholder.svg';

        $user = User::where('role', 'admin')->first() ?? User::first();
        if (!$user) {
            $user = User::factory()->create(['username' => 'newsbot', 'email' => 'news@laranews.test']);
        }

        $content = (string) ($attrs['content'] ?? '');

        $post = Post::create([
            'user_id' => $user->id,
            'topic_id' => $topic->id,
            'title' => $title,
            'slug' => Str::slug($title) . '-' . uniqid(),
            'content' => $content,
            'content_html' => (string) ($attrs['content_html'] ?? ''),
            'meta_title' => Str::limit($title, 60),
            'meta_description' => Str::limit(strip_tags($content), 160),
            'post_type' => 'text',
            'image_url' => $imageUrl,
            'thumbnail_url' => $thumbnailUrl,
            'source_name' => (string) ($attrs['source_name'] ?? ''),
            'source_url' => $attrs['source_url'] ?? null,
            'canonical_url' => $canonical,
            'status' => 'published',
            'published_at' => $attrs['published_at'] ?? now(),
            'last_activity_at' => now(),
            'is_approved' => true,
            'vote_score' => 0,
            'upvote_count' => 0,
            'downvote_count' => 0,
            'comment_count' => 0,
            'view_count' => 0,
            'bookmark_count' => 0,
            'hot_score' => 0,
        ]);

        app(HotScoreService::class)->recalculate($post);
        return 'imported';
    }

    protected function normalizeUrl(string $url): string
    {
        $parsed = parse_url($url);
        if (!$parsed) return $url;
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        $path = $parsed['path'] ?? '';
        $query = '';
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $params);
            unset($params['utm_source'], $params['utm_medium'], $params['utm_campaign'], $params['utm_term'], $params['utm_content']);
            $query = http_build_query($params);
        }
        $normalized = $scheme . '://' . $host . $path;
        if ($query) $normalized .= '?' . $query;
        return $normalized;
    }

    protected function classifyTopic(string $title): ?string
    {
        $map = [
            'ai' => 'artificial-intelligence',
            'openai' => 'artificial-intelligence',
            'gpt' => 'artificial-intelligence',
            'machine learning' => 'artificial-intelligence',
            'bitcoin' => 'web3-blockchain',
            'crypto' => 'web3-blockchain',
            'ethereum' => 'web3-blockchain',
            'space' => 'space-astronomy',
            'nasa' => 'space-astronomy',
            'spacex' => 'space-astronomy',
            'mars' => 'space-astronomy',
            'climate' => 'climate-tech',
            'solar' => 'climate-tech',
            'battery' => 'climate-tech',
            'quantum' => 'climate-tech',
            'startup' => 'startups',
            'funding' => 'startups',
            'saas' => 'startups',
            'apple' => 'mobile-technology',
            'iphone' => 'mobile-technology',
            'google' => 'software-development',
            'microsoft' => 'software-development',
            'laravel' => 'software-development',
            'react' => 'software-development',
            'typescript' => 'software-development',
            'security' => 'cybersecurity',
            'hacker' => 'cybersecurity',
            'ransomware' => 'cybersecurity',
            'playstation' => 'hardware-gadgets',
            'gaming' => 'hardware-gadgets',
            'tesla' => 'hardware-gadgets',
        ];
        $lower = strtolower($title);
        foreach ($map as $key => $slug) {
            if (str_contains($lower, $key)) {
                return $slug;
            }
        }
        return null;
    }

    /**
     * Extract candidate image URLs from an RSS item (enclosure, media:*,
     * first <img> in description/content). Pure string extraction, no I/O.
     */
    protected function extractImageCandidates($item): array
    {
        $candidates = [];

        // 1) Enclosure (gambar asli dari sumber)
        if (isset($item->enclosure)) {
            $url = trim((string) $item->enclosure['url']);
            if ($url !== '') {
                $candidates[] = $url;
            }
        }

        // 2) media:content / media:thumbnail
        $media = $item->children('media', true);
        foreach (['content', 'thumbnail'] as $tag) {
            if (isset($media->{$tag})) {
                $url = trim((string) $media->{$tag}['url']);
                if ($url !== '') {
                    $candidates[] = $url;
                }
            }
        }

        // 3) <img> pertama di dalam description/content
        foreach ([$item->children('content', true)->encoded ?? null, $item->description ?? null] as $htmlSource) {
            if (!empty($htmlSource) && preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', (string) $htmlSource, $m)) {
                $candidates[] = $m[1];
                break;
            }
        }

        return $candidates;
    }

    /**
     * Download the first candidate that succeeds; otherwise fall back to the
     * keyword/topic-based resolver (which itself falls back to placeholder).
     */
    protected function resolveImageFromCandidates(array $candidates, string $title, string $topicName): array
    {
        foreach ($candidates as $candidateUrl) {
            $stored = $this->imageService->downloadRemote($candidateUrl);
            if ($stored !== null) {
                Log::info('[NewsIngest] using original article image', ['title' => $title, 'url' => $candidateUrl]);
                return $stored;
            }
        }

        // Fallback: resolver berbasis keyword/kategori
        return $this->imageResolver->resolveAndStore($title, $topicName)
            ?? ['image_url' => '/storage/images/placeholder.svg', 'thumbnail_url' => '/storage/images/placeholder.svg'];
    }
}
