<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ImageResolver
{
    public const PLACEHOLDER_URL = '/storage/images/placeholder.svg';

    // Entity map: keywords → search query (audit-driven, word-boundary matching, longest-first)
    // Sorted by key length descending during runtime matching
    private const ENTITY_QUERIES = [
        // Multi-word entities (check first)
        'machine learning' => 'machine learning artificial intelligence',
        'artificial intelligence' => 'artificial intelligence technology',
        'github copilot' => 'github copilot ai code editor',
        'apple intelligence' => 'apple intelligence ai',
        'data breach' => 'cybersecurity data breach',
        'mental health' => 'mental health technology',
        'remote work' => 'remote work office laptop',
        '4-day work week' => 'remote work office',
        'universal basic income' => 'society economics',
        'james webb' => 'james webb space telescope',
        'space station' => 'space station orbit',
        'lab-grown' => 'biotechnology laboratory',
        'net energy' => 'fusion energy reactor',
        'carbon capture' => 'carbon capture climate technology',
        'grid-scale' => 'battery energy storage',
        'design system' => 'design system ui ux',
        'mixed reality' => 'virtual reality vr headset',
        'digital nomad' => 'remote work travel laptop',
        'customer service' => 'customer service technology',
        
        // Programming/dev tools
        'typescript' => 'typescript programming code',
        'javascript' => 'javascript programming code',
        'laravel' => 'php laravel web development',
        'react' => 'react javascript web development',
        'node.js' => 'nodejs javascript server',
        'nodejs' => 'nodejs javascript server',
        'bun' => 'bun javascript runtime',
        'deno' => 'deno javascript runtime',
        'compiler' => 'programming compiler code',
        
        // AI/LLM
        'openai' => 'openai artificial intelligence',
        'gpt-5' => 'gpt artificial intelligence',
        'gpt-4' => 'gpt artificial intelligence',
        'gpt' => 'artificial intelligence chatbot',
        'deepmind' => 'deepmind artificial intelligence',
        'anthropic' => 'anthropic artificial intelligence',
        'llm' => 'large language model ai',
        'ai model' => 'artificial intelligence',
        'ai agent' => 'artificial intelligence robot',
        'ai gateway' => 'artificial intelligence cloud',
        'ai safety' => 'artificial intelligence ethics',
        'ai act' => 'artificial intelligence regulation policy',
        'ai-powered' => 'artificial intelligence technology',
        'ai-generated' => 'artificial intelligence generated content',
        'microsoft ai' => 'microsoft ai technology',
        
        // Security
        'zero-day' => 'cybersecurity hacking vulnerability',
        'exploit' => 'cybersecurity hacking',
        'ransomware' => 'ransomware cybersecurity attack',
        'hacker' => 'hacker cybersecurity',
        'cybersecurity' => 'cybersecurity network security',
        'password' => 'password security authentication',
        'breach' => 'data breach cybersecurity',
        
        // Cloud/infra
        'aws' => 'amazon web services cloud',
        'graviton' => 'aws graviton chip processor',
        'cloudflare' => 'cloudflare cloud network',
        'vercel' => 'vercel web development deployment',
        'docker' => 'docker container technology',
        
        // Crypto/fintech
        'bitcoin' => 'bitcoin cryptocurrency',
        'ethereum' => 'ethereum blockchain',
        'crypto' => 'cryptocurrency blockchain',
        'blockchain' => 'blockchain technology',
        'coinbase' => 'coinbase cryptocurrency exchange',
        'solana' => 'solana blockchain cryptocurrency',
        'stablecoin' => 'stablecoin cryptocurrency',
        'fintech' => 'fintech finance technology',
        'paypal' => 'paypal payment technology',
        'robinhood' => 'robinhood trading finance',
        'square' => 'square payment technology',
        'stripe' => 'stripe payment technology',
        'plaid' => 'plaid fintech banking',
        
        // Mobile
        'iphone' => 'apple iphone smartphone',
        'samsung' => 'samsung smartphone technology',
        'galaxy' => 'samsung galaxy smartphone',
        'pixel' => 'google pixel smartphone',
        'google pixel' => 'google pixel smartphone',
        'vision pro' => 'apple vision pro vr headset',
        
        // Hardware
        'amd' => 'amd processor chip',
        'ryzen' => 'amd ryzen processor',
        'intel' => 'intel processor chip',
        'nvidia' => 'nvidia graphics card gpu',
        'geforce' => 'nvidia geforce graphics card',
        'rtx' => 'nvidia rtx graphics card',
        'framework laptop' => 'framework modular laptop',
        'chips' => 'computer chip semiconductor',
        'chip' => 'computer chip semiconductor',
        'semiconductor' => 'semiconductor chip technology',
        
        // Robotics/EV
        'tesla' => 'tesla electric car',
        'optimus' => 'robot humanoid technology',
        'robot' => 'robot technology',
        'electric car' => 'electric car vehicle',
        
        // Gaming
        'playstation' => 'playstation gaming console',
        'quest' => 'meta quest vr headset',
        'gaming' => 'gaming video game',
        '16k' => 'gaming console technology',
        
        // Startups/business
        'y combinator' => 'y combinator startup office',
        'startup' => 'startup office entrepreneur',
        'valuation' => 'startup valuation investment',
        'series c' => 'startup funding investment',
        'series d' => 'startup funding investment',
        'arr' => 'saas business revenue',
        'saas' => 'saas software business',
        'funding' => 'startup funding investment',
        'layoff' => 'corporate layoffs office',
        'earnings' => 'business earnings finance',
        'ipo' => 'ipo stock market finance',
        'revenue' => 'business revenue finance',
        'profitable' => 'business profit finance',
        
        // Space
        'spacex' => 'spacex rocket launch',
        'starship' => 'spacex starship rocket',
        'mars' => 'mars planet space',
        'nasa' => 'nasa space astronomy',
        'moon' => 'moon space astronomy',
        'lunar' => 'lunar moon space',
        'telescope' => 'space telescope astronomy',
        'blue origin' => 'blue origin rocket space',
        'satellite' => 'satellite space orbit',
        'biosignature' => 'space astronomy exoplanet',
        
        // Biotech/health
        'crispr' => 'crispr dna gene editing',
        'sickle cell' => 'sickle cell disease medicine',
        'moderna' => 'moderna vaccine biotechnology',
        'vaccine' => 'vaccine medicine biotechnology',
        'neuralink' => 'neuralink brain implant technology',
        'alzheimer' => 'alzheimer disease medicine',
        'medicine' => 'medicine healthcare',
        
        // Quantum/fusion/energy
        'quantum' => 'quantum computing technology',
        'rsa-2048' => 'encryption cryptography security',
        'fusion' => 'fusion energy reactor',
        'reactor' => 'nuclear reactor energy',
        'battery' => 'battery energy storage',
        'solar' => 'solar panel energy',
        'climate' => 'climate change environment',
        'carbon' => 'carbon emissions climate',
        
        // Design
        'figma' => 'figma design ui ux',
        'adobe' => 'adobe creative design software',
        'firefly' => 'adobe firefly ai design',
        'canva' => 'canva design graphic',
        'designer' => 'designer creative ux ui',
        
        // Social/media
        'tiktok' => 'tiktok social media',
        'youtube' => 'youtube video social media',
        'spotify' => 'spotify music streaming',
        'netflix' => 'netflix streaming entertainment',
        'threads' => 'threads social media',
        'twitter' => 'twitter social media',
        'instagram' => 'instagram social media',
        'facebook' => 'facebook social media',
        'gen z' => 'generation z youth culture',
        'creator' => 'content creator social media',
        'stream' => 'streaming media technology',
        
        // Policy/geopolitics
        'regulation' => 'government regulation policy',
        'senate' => 'senate government politics',
        'privacy' => 'privacy data security',
        'treaty' => 'treaty international policy',
        'fine' => 'regulation fine penalty',
        'ban' => 'regulation policy ban',
        
        // Work/society
        'burnout' => 'work stress burnout',
        'education' => 'education technology learning',
        'democracy' => 'democracy politics society',
        
        // Generic fallbacks (last)
        'apple' => 'apple technology products',
        'google' => 'google technology',
        'microsoft' => 'microsoft technology software',
        'meta' => 'meta facebook technology',
        'windows' => 'microsoft windows computer',
        'space' => 'space astronomy',
        'ai' => 'artificial intelligence',
    ];

    // Category fallback: topic keyword → pool of stable Unsplash CDN photo URLs (3-4 per category).
    // All URLs verified live. Tried in order (A, B, C, D) until one downloads successfully.
    private const CATEGORY_FALLBACKS = [
        'artificial intelligence' => [
            'https://images.unsplash.com/photo-1677442136019-21780ecad995?w=1200',
            'https://images.unsplash.com/photo-1620712943543-bcc4688e7485?w=1200',
            'https://images.unsplash.com/photo-1591453089816-0fbb971b454c?w=1200',
            'https://images.unsplash.com/photo-1485827404703-89b55fcc595e?w=1200',
        ],
        'software development' => [
            'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?w=1200',
            'https://images.unsplash.com/photo-1555066931-4365d14bab8c?w=1200',
            'https://images.unsplash.com/photo-1587620962725-abab7fe55159?w=1200',
            'https://images.unsplash.com/photo-1542831371-29b0f74f9713?w=1200',
        ],
        'cybersecurity' => [
            'https://images.unsplash.com/photo-1563013544-824ae1b704d3?w=1200',
            'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?w=1200',
            'https://images.unsplash.com/photo-1614064641938-3bbee52942c7?w=1200',
            'https://images.unsplash.com/photo-1515879218367-8466d910aaa4?w=1200',
        ],
        'cloud' => [
            'https://images.unsplash.com/photo-1544197150-b99a580bb7a8?w=1200',
            'https://images.unsplash.com/photo-1558494949-ef010cbdcc31?w=1200',
            'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=1200',
            'https://images.unsplash.com/photo-1517077304055-6e89abbf09b0?w=1200',
        ],
        'blockchain' => [
            'https://images.unsplash.com/photo-1621761191319-c6fb62004040?w=1200',
            'https://images.unsplash.com/photo-1639762681485-074b7f938ba0?w=1200',
            'https://images.unsplash.com/photo-1639322537228-f710d846310a?w=1200',
            'https://images.unsplash.com/photo-1518546305927-5a555bb7020d?w=1200',
        ],
        'mobile' => [
            'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=1200',
            'https://images.unsplash.com/photo-1512941937669-90a1b58e7e9c?w=1200',
            'https://images.unsplash.com/photo-1556656793-08538906a9f8?w=1200',
            'https://images.unsplash.com/photo-1503676260728-1c00da094a0b?w=1200',
        ],
        'hardware' => [
            'https://images.unsplash.com/photo-1553406830-ef2513450d76?w=1200',
            'https://images.unsplash.com/photo-1518770660439-4636190af475?w=1200',
            'https://images.unsplash.com/photo-1591799264318-7e6ef8ddb7ea?w=1200',
            'https://images.unsplash.com/photo-1559757148-5c350d0d3c56?w=1200',
        ],
        'startup' => [
            'https://images.unsplash.com/photo-1559136555-9303baea8ebd?w=1200',
            'https://images.unsplash.com/photo-1556761175-b413da4baf72?w=1200',
            'https://images.unsplash.com/photo-1522071820081-009f0129c71c?w=1200',
            'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=1200',
        ],
        'business' => [
            'https://images.unsplash.com/photo-1507679799987-c73779587ccf?w=1200',
            'https://images.unsplash.com/photo-1454165804606-c3d57bc86b40?w=1200',
            'https://images.unsplash.com/photo-1553877522-43269d4ea984?w=1200',
            'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?w=1200',
        ],
        'finance' => [
            'https://images.unsplash.com/photo-1611974789855-9c2a0a7236a3?w=1200',
            'https://images.unsplash.com/photo-1526304640581-d334cdbbf45e?w=1200',
            'https://images.unsplash.com/photo-1535378917042-10a22c95931a?w=1200',
            'https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=1200',
        ],
        'tech industry' => [
            'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?w=1200',
            'https://images.unsplash.com/photo-1486312338219-ce68d2c6f44d?w=1200',
            'https://images.unsplash.com/photo-1498050108023-c5249f4df085?w=1200',
            'https://images.unsplash.com/photo-1517694712202-14dd9538aa97?w=1200',
        ],
        'space' => [
            'https://images.unsplash.com/photo-1446776811953-b23d57bd21aa?w=1200',
            'https://images.unsplash.com/photo-1444703686981-a3abbc4d4fe3?w=1200',
            'https://images.unsplash.com/photo-1454789548928-9efd52dc4031?w=1200',
            'https://images.unsplash.com/photo-1472214103451-9374bd1c798e?w=1200',
        ],
        'biotechnology' => [
            'https://images.unsplash.com/photo-1532187863486-abf9dbad1b69?w=1200',
            'https://images.unsplash.com/photo-1576086213369-97a306d36557?w=1200',
            'https://images.unsplash.com/photo-1582719508461-905c673771fd?w=1200',
            'https://images.unsplash.com/photo-1584036561566-baf8f5f1b144?w=1200',
        ],
        'climate' => [
            'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?w=1200',
            'https://images.unsplash.com/photo-1501594907352-04cda38ebc29?w=1200',
            'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?w=1200',
            'https://images.unsplash.com/photo-1593305841991-05c297ba4575?w=1200',
        ],
        'design' => [
            'https://images.unsplash.com/photo-1558655146-d09347e92766?w=1200',
            'https://images.unsplash.com/photo-1561070791-2526d30994b5?w=1200',
            'https://images.unsplash.com/photo-1545235617-9465d2a55698?w=1200',
            'https://images.unsplash.com/photo-1509391366360-2e959784a276?w=1200',
        ],
        'digital culture' => [
            'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=1200',
            'https://images.unsplash.com/photo-1516251193007-45ef944ab0c6?w=1200',
            'https://images.unsplash.com/photo-1466611653911-95081537e5b7?w=1200',
            'https://images.unsplash.com/photo-1473341304170-971dccb5ac1e?w=1200',
        ],
        'global affairs' => [
            'https://images.unsplash.com/photo-1529107386315-e1a2ed48a620?w=1200',
            'https://images.unsplash.com/photo-1521791136064-7986c2920216?w=1200',
            'https://images.unsplash.com/photo-1495020689067-958852a7765e?w=1200',
            'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=1200',
        ],
        'tech policy' => [
            'https://images.unsplash.com/photo-1521791136064-7986c2920216?w=1200',
            'https://images.unsplash.com/photo-1507925921958-8a62f3d1a50d?w=1200',
            'https://images.unsplash.com/photo-1521587760476-6c12a4b040da?w=1200',
            'https://images.unsplash.com/photo-1527430253228-e93688616381?w=1200',
        ],
        'future of work' => [
            'https://images.unsplash.com/photo-1521737711867-e3b97375f902?w=1200',
            'https://images.unsplash.com/photo-1497215728101-856f4ea42174?w=1200',
            'https://images.unsplash.com/photo-1556761175-5973dc0f32e7?w=1200',
            'https://images.unsplash.com/photo-1483478550801-ceba5fe50e8e?w=1200',
        ],
        'digital society' => [
            'https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=1200',
            'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=1200',
            'https://images.unsplash.com/photo-1516192518150-0d8fee5425e3?w=1200',
            'https://images.unsplash.com/photo-1555949963-aa79dcee981c?w=1200',
        ],
        'gaming' => [
            'https://images.unsplash.com/photo-1598550476439-6847785fcea6?w=1200',
            'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=1200',
            'https://images.unsplash.com/photo-1552820728-8b83bb6b773f?w=1200',
            'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=1200',
        ],
        'society' => [
            'https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=1200',
            'https://images.unsplash.com/photo-1517486808906-6ca8b3f04846?w=1200',
            'https://images.unsplash.com/photo-1543269865-cbf427effbad?w=1200',
            'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=1200',
        ],
        'technology' => [
            'https://images.unsplash.com/photo-1518770660439-4636190af475?w=1200',
            'https://images.unsplash.com/photo-1526374965328-7f61d4dc18c5?w=1200',
            'https://images.unsplash.com/photo-1531297484001-80022131f5a1?w=1200',
            'https://images.unsplash.com/photo-1560958089-b8a1929cea89?w=1200',
        ],
    ];

    private const TOPIC_QUERIES = [
        'artificial intelligence' => 'artificial intelligence',
        'software development' => 'software development',
        'cybersecurity' => 'cybersecurity',
        'cloud' => 'cloud infrastructure',
        'blockchain' => 'blockchain',
        'web3' => 'blockchain',
        'mobile' => 'smartphone technology',
        'hardware' => 'computer hardware',
        'gadgets' => 'technology gadgets',
        'startups' => 'startup office',
        'business' => 'business strategy',
        'finance' => 'finance markets',
        'markets' => 'finance markets',
        'tech industry' => 'technology industry',
        'space' => 'space astronomy',
        'astronomy' => 'space astronomy',
        'biotechnology' => 'biotechnology laboratory',
        'climate' => 'climate environment',
        'design' => 'design creative',
        'ux' => 'design ui ux',
        'culture' => 'digital culture',
        'global' => 'global news world',
        'affairs' => 'global news world',
        'policy' => 'government policy',
        'future of work' => 'remote work office',
        'work' => 'workplace technology',
        'society' => 'modern society',
        'digital' => 'digital technology',
    ];

    public function __construct(private readonly ImageService $images)
    {
    }

    /**
     * Detect entity from title using word-boundary regex, return query or null.
     * Exported for testability.
     */
    public function detectEntity(string $title): ?string
    {
        $lower = mb_strtolower($title, 'UTF-8');

        // Sort keys by length descending (longest match first)
        $keys = array_keys(self::ENTITY_QUERIES);
        usort($keys, static fn (string $a, string $b) => strlen($b) <=> strlen($a));

        foreach ($keys as $key) {
            $pattern = '/\b' . preg_quote($key, '/') . '\b/ui';
            if (preg_match($pattern, $lower)) {
                return self::ENTITY_QUERIES[$key];
            }
        }

        return null;
    }

    /**
     * Resolve + download + store with robust fallback chain. Always returns local paths (or placeholder).
     * Chain: entity-based query → topic-based query → API (if key present) → category pool URLs (A, B, C, D) → placeholder.
     * Never throws.
     *
     * @return array{image_url: string, thumbnail_url: string}
     */
    public function resolveAndStore(string $title, string $topicName): array
    {
        $candidates = $this->buildCandidateChain($title, $topicName);

        foreach ($candidates as $i => $url) {
            // Lewati kandidat yang bukan URL valid (hindari "Array to string conversion").
            if (!is_string($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            try {
                $stored = $this->images->downloadRemote($url);
            } catch (\Throwable $e) {
                Log::warning('[ImageResolver] downloadRemote exception', [
                    'title' => $title,
                    'url' => is_string($url) ? $url : json_encode($url),
                    'error' => $e->getMessage(),
                ]);
                continue;
            }

            if ($stored !== null) {
                Log::debug('[ImageResolver] candidate succeeded', [
                    'title' => $title,
                    'topic' => $topicName,
                    'candidate_index' => $i,
                    'url' => $url,
                ]);
                return $stored;
            }

            Log::debug('[ImageResolver] candidate failed, trying next', [
                'title' => $title,
                'topic' => $topicName,
                'candidate_index' => $i,
                'url' => $url,
            ]);
        }

        Log::warning('[ImageResolver] all candidates failed, using placeholder', [
            'title' => $title,
            'topic' => $topicName,
            'candidate_count' => count($candidates),
        ]);

        return $this->placeholder();
    }

    /**
     * Build ordered list of candidate image URLs to try.
     * Order: Unsplash API (if key set + query resolved) → category fallback pool (all URLs in order).
     *
     * @return string[]
     */
    private function buildCandidateChain(string $title, string $topicName): array
    {
        $query = $this->detectEntity($title) ?? $this->topicQuery($topicName);

        $candidates = [];

        // Hasil search Unsplash, dirotasi per-artikel supaya tidak ada dua
        // artikel berdekatan memakai foto yang sama walau query-nya identik.
        if ($query !== null) {
            foreach ($this->rotateForTitle($this->searchUnsplashList($query), $title) as $url) {
                if (is_string($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                    $candidates[] = $url;
                }
            }
        }

        // Pool fallback kategori juga dirotasi per-artikel.
        $category = $this->detectCategory($topicName);
        $fallbackPool = self::CATEGORY_FALLBACKS[$category] ?? self::CATEGORY_FALLBACKS['technology'];
        foreach ($this->rotateForTitle($fallbackPool, $title) as $url) {
            if (is_string($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                $candidates[] = $url;
            }
        }

        return array_values(array_unique($candidates));
    }

    private function topicQuery(string $topicName): ?string
    {
        $lower = strtolower($topicName);

        foreach (self::TOPIC_QUERIES as $needle => $query) {
            if (str_contains($lower, $needle)) {
                return $query;
            }
        }

        return null;
    }

    private function rotateForTitle(array $items, string $title): array
    {
        if (empty($items)) {
            return [];
        }
        $hash = abs(crc32($title));
        $offset = $hash % count($items);
        return array_merge(
            array_slice($items, $offset),
            array_slice($items, 0, $offset)
        );
    }

    private function searchUnsplash(string $query): ?string
    {
        $accessKey = config('services.unsplash.access_key');
        if (!$accessKey) {
            return null;
        }

        $cacheKey = 'image_resolver_unsplash_' . md5($query);

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($query) {
            try {
                $response = Http::timeout(10)->get('https://api.unsplash.com/photos/random', [
                    'client_id' => config('services.unsplash.access_key'),
                    'query' => $query,
                    'orientation' => 'landscape',
                ]);

                if (!$response->successful()) {
                    return null;
                }

                $url = $response->json('urls.regular');

                return is_string($url) && $url !== '' ? $url : null;
            } catch (\Throwable $e) {
                Log::warning('[ImageResolver] unsplash search failed', [
                    'query' => $query,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    private function searchUnsplashList(string $query): array
    {
        $accessKey = config('services.unsplash.access_key');
        if (!$accessKey) {
            return [];
        }

        $cacheKey = 'image_resolver_unsplash_list_' . md5($query);
        return Cache::remember($cacheKey, now()->addHours(6), function () use ($query, $accessKey) {
            try {
                $response = Http::timeout(10)->get('https://api.unsplash.com/photos/random', [
                    'client_id' => $accessKey,
                    'query' => $query,
                    'orientation' => 'landscape',
                    'count' => 5,
                ]);
                if (!$response->successful()) {
                    return [];
                }

                $data = $response->json();
                if (!is_array($data)) {
                    return [];
                }

                // Ekstrak hanya URL string yang valid dari setiap hasil.
                $urls = [];
                foreach ($data as $item) {
                    if (is_array($item) && isset($item['urls']['regular']) && is_string($item['urls']['regular'])) {
                        $urls[] = $item['urls']['regular'];
                    }
                }

                return array_values(array_unique(array_filter($urls, fn ($u) => is_string($u) && filter_var($u, FILTER_VALIDATE_URL))));
            } catch (\Throwable $e) {
                Log::warning('[ImageResolver] unsplash list failed', ['query' => $query]);
                return [];
            }
        });
    }

    private function detectCategory(string $topicName): string
    {
        $lower = strtolower($topicName);

        foreach (self::CATEGORY_FALLBACKS as $category => $pool) {
            if (str_contains($lower, $category)) {
                return $category;
            }
        }

        return 'technology';
    }

    /**
     * @return array{image_url: string, thumbnail_url: string}
     */
    private function placeholder(): array
    {
        return [
            'image_url' => self::PLACEHOLDER_URL,
            'thumbnail_url' => self::PLACEHOLDER_URL,
        ];
    }
}
