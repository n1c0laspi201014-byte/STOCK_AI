<?php
declare(strict_types=1);

use App\Services\OpenRouterService;
use App\Support\Container;

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';

$model = $argv[1] ?? null;
$started = microtime(true);
$result = Container::get(OpenRouterService::class)->analyze([
    'stock' => ['symbol' => 'NVDA'],
    'quote' => ['price' => 100, 'change_percent' => 1.2],
    'technical_score' => 61,
    'market_score' => 50,
    'news' => [['headline' => 'Neutral test headline']],
    'horizon' => '7d',
    'disclaimer' => config('app.disclaimer'),
], $model);
$elapsed = microtime(true) - $started;

echo 'SUCCESS=' . (!empty($result['success']) ? 'true' : 'false') . PHP_EOL;
echo 'MODEL=' . (string) ($result['model'] ?? $model ?? 'configured default') . PHP_EOL;
echo 'ERROR_CODE=' . (string) ($result['error_code'] ?? 'none') . PHP_EOL;
echo 'MESSAGE=' . (string) ($result['message'] ?? 'none') . PHP_EOL;
echo 'SECONDS=' . number_format($elapsed, 3) . PHP_EOL;
exit(!empty($result['success']) ? 0 : 1);
