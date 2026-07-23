<?php

use App\Config\Env;

return [
    'model' => Env::get('OPENROUTER_MODEL', 'nvidia/nemotron-3-super-120b-a12b:free'),
    'default_horizon' => '7d',
    'technical_weight' => 0.50,
    'news_weight' => 0.30,
    'market_weight' => 0.20,
    'buy_probability' => 70,
    'watch_probability' => 58,
    'sell_probability_down' => 70,
    'minimum_confidence' => 60,
    'expires_hours' => 6,
    'max_discovery_candidates' => 20,
    'max_discovery_results' => 5,
];
