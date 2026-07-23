<?php

use App\Config\Env;

return [
    'name' => Env::get('APP_NAME', 'STOCK AI'),
    'env' => Env::get('APP_ENV', 'production'),
    'debug' => Env::bool('APP_DEBUG', false),
    'url' => rtrim((string) Env::get('APP_URL', 'http://localhost/papertrade-ai/public'), '/'),
    'timezone' => Env::get('APP_TIMEZONE', 'Europe/Brussels'),
    'session_name' => Env::get('SESSION_NAME', 'papertrade_session'),
    'disclaimer' => 'This application is a paper-trading educational project. Market predictions are uncertain estimates generated from available data and are not financial advice. No real trades are placed.',
];
