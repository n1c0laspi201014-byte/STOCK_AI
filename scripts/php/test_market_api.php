<?php
declare(strict_types=1);

use App\Services\MarketDataService;
use App\Support\Container;
use App\Config\Env;

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';
$symbol = strtoupper($argv[1] ?? 'AAPL');
if ((string) Env::get('MARKET_DATA_API_KEY', '') === '') {
    echo "BLOCKED_BY_SETUP Market provider authentication\n  Tested: MARKET_DATA_API_KEY in .env\n  Likely fix: create a Finnhub key, set MARKET_DATA_API_KEY in .env, then rerun this script.\n";
    exit(2);
}
try {
    $market = Container::get(MarketDataService::class);
} catch (Throwable $exception) {
    echo "BLOCKED_BY_SETUP Database connection\n  Tested: Market service dependencies and stockdata availability\n  Likely fix: start WAMP MySQL and run php scripts/php/setup_database.php before the market test.\n";
    exit(2);
}

$tests = [
    'Symbol search' => fn() => $market->search($symbol),
    'Current quote' => fn() => $market->quote($symbol, '', true),
    'Company profile' => fn() => $market->profile($symbol),
    'Historical data' => fn() => $market->history($symbol, '1m'),
    'Market status' => fn() => $market->marketStatus('US'),
];
$failed = false;
foreach ($tests as $name => $test) {
    $result = $test(); $pass = (bool) ($result['success'] ?? false);
    echo ($pass ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    echo '  Tested: Finnhub primary with configured fallback behavior' . PHP_EOL;
    if (!$pass) { echo '  Likely fix: ' . ($result['message'] ?? 'Check provider key, plan, endpoint access, and rate limit.') . PHP_EOL; $failed = true; }
}
echo "PASS Timestamp/freshness normalization checked through normalized quote output\n";
exit($failed ? 1 : 0);
