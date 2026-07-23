<?php

use App\Config\Env;

return [
    'provider' => Env::get('MARKET_DATA_PROVIDER', 'finnhub'),
    'api_key' => Env::get('MARKET_DATA_API_KEY', ''),
    'fallback_provider' => Env::get('MARKET_DATA_FALLBACK_PROVIDER', 'twelvedata'),
    'fallback_api_key' => Env::get('MARKET_DATA_FALLBACK_API_KEY', ''),
    'cache_seconds' => (int) Env::get('MARKET_DATA_CACHE_SECONDS', 60),
    'stale_after_seconds' => (int) Env::get('MARKET_DATA_STALE_AFTER_SECONDS', 900),
    'demo_universe' => ['AAPL', 'MSFT', 'NVDA', 'AMZN', 'GOOGL', 'META', 'TSLA', 'AMD'],
];

