<?php

// RSS sources disabled: replaced by DataSectors API ingestion
// (`news:fetch --provider=datasectors`, default). RSS kept as manual
// fallback via `news:fetch --provider=rss`. Do not delete entries.

return [
    [
        'name' => 'The Verge',
        'url' => 'https://www.theverge.com/rss/index.xml',
        'enabled' => false, // Atom feed not supported
        'default_topic' => 'tech-industry',
    ],
    [
        'name' => 'TechCrunch',
        'url' => 'https://techcrunch.com/feed/',
        'enabled' => false,
        'default_topic' => 'startups',
    ],
    [
        'name' => 'Ars Technica',
        'url' => 'https://feeds.arstechnica.com/arstechnica/index',
        'enabled' => false,
        'default_topic' => 'software-development',
    ],
    [
        'name' => 'Reuters Technology',
        'url' => 'https://www.reuters.com/technology/feed/',
        'enabled' => false, // Returns 401
        'default_topic' => 'technology',
    ],
    [
        'name' => 'BBC Tech/Science',
        'url' => 'https://feeds.bbci.co.uk/news/technology/rss.xml',
        'enabled' => false,
        'default_topic' => 'technology',
    ],
];
