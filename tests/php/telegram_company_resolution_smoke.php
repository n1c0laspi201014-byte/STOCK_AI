<?php
declare(strict_types=1);

use App\Services\TelegramQuestionService;
use App\Support\Container;

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';

$service = Container::get(TelegramQuestionService::class);
$checks = [
    '/stock NVIDIA' => 'NVDA',
    'Apple' => 'AAPL',
    'what about Tesla?' => 'TSLA',
    'stock Microsoft' => 'MSFT',
    '/stock Berkshire Hathaway' => 'BRK.A',
    '$META' => 'META',
];
$failed = false;
foreach ($checks as $message => $expected) {
    $result = $service->resolve($message);
    $passes = !empty($result['success']) && ($result['symbol'] ?? null) === $expected;
    echo ($passes ? 'PASS ' : 'FAIL ') . $message . ' resolves to ' . $expected . PHP_EOL;
    $failed = $failed || !$passes;
}

$help = $service->resolve('/stock');
$helpPasses = empty($help['success']) && str_contains((string) ($help['message'] ?? ''), 'company name or ticker');
echo ($helpPasses ? 'PASS ' : 'FAIL ') . 'Empty stock command returns company-name help' . PHP_EOL;
$failed = $failed || !$helpPasses;

exit($failed ? 1 : 0);
