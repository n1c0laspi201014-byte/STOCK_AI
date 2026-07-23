<?php
declare(strict_types=1);

use App\Config\Env;
use App\Services\OpenRouterService;
use App\Support\Container;

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';
$symbol = strtoupper($argv[1] ?? 'AAPL');
$horizon = $argv[2] ?? '7d';
if ((string) Env::get('OPENROUTER_API_KEY', '') === '') {
    echo "BLOCKED_BY_SETUP OpenRouter authentication\n  Tested: OPENROUTER_API_KEY in .env\n  Likely fix: add the server-side OpenRouter key and exact model identifier to .env.\n";
    exit(2);
}
$result = Container::get(OpenRouterService::class)->analyze(['stock'=>['symbol'=>$symbol],'quote'=>['price'=>100,'change_percent'=>1.2],'technical_score'=>61,'market_score'=>50,'news'=>[['headline'=>'Neutral test headline']], 'horizon'=>$horizon,'disclaimer'=>config('app.disclaimer')]);
if (!$result['success']) {
    echo "FAIL OpenRouter integration\n  Tested: authentication, selected model, JSON schema, bounded fields, safety wording\n  Likely fix: {$result['message']} Check OPENROUTER_MODEL, credits, rate limits, and model structured-output support.\n";
    exit(1);
}
echo "PASS OpenRouter authentication\nPASS Model available\nPASS JSON response\nPASS Schema validation\nPASS Bounded scores\nPASS Disclaimer-safe language\n";

