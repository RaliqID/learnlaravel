<?php

return [
    'api_key' => env('DATASECTORS_API_KEY'),
    'base_url' => 'https://api.datasectors.com',
    'timeout' => 30,
    'max_results' => 100,          // per request, stays well under 500
    'market_types' => ['stock', 'crypto', 'economic'],  // filter for tech/finance-relevant news
    // marketType → default topic slug mapping (used by ingest service)
    'market_type_topics' => [
        'stock' => 'finance-markets',
        'crypto' => 'web3-blockchain',
        'forex' => 'finance-markets',
        'etf' => 'finance-markets',
        'index' => 'finance-markets',
        'futures' => 'finance-markets',
        'bond' => 'finance-markets',
        'corp_bond' => 'finance-markets',
        'economic' => 'business-strategy',
    ],
];
