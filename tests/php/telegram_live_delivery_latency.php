<?php
declare(strict_types=1);

use App\Config\Database;
use App\Integrations\Telegram\TelegramTestClient;
use App\Services\TelegramQuestionService;
use App\Support\Container;

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';

$pdo = Database::connection();
$connection = $pdo->query(
    "SELECT tc.chat_id
     FROM users u
     JOIN telegram_connections tc
       ON tc.user_id = u.id
      AND tc.is_enabled = 1
      AND tc.is_verified = 1
     WHERE u.email = 'admin@papertrade.local'
     LIMIT 1"
)->fetch();
if (!$connection) {
    throw new RuntimeException('Verified Admin Telegram connection not found.');
}

$started = microtime(true);
$answer = Container::get(TelegramQuestionService::class)->answer(
    (string) $connection['chat_id'],
    '/stock NVDA'
);
$contextElapsed = microtime(true) - $started;

$message = "STOCK AI FAST-REPLY TEST\n"
    . 'Prepared in ' . number_format($contextElapsed, 3) . " seconds.\n"
    . "The repaired /stock path now bypasses OpenRouter.\n\n"
    . (string) ($answer['message'] ?? '');

$sendStarted = microtime(true);
$delivery = Container::get(TelegramTestClient::class)->send(
    (string) $connection['chat_id'],
    $message
);
$sendElapsed = microtime(true) - $sendStarted;
$totalElapsed = microtime(true) - $started;

$checks = [
    'NVDA factual answer was prepared' => ($answer['symbol'] ?? null) === 'NVDA',
    'Telegram accepted the real test message' => !empty($delivery['success']) && !empty($delivery['message_id']),
    'End-to-end direct delivery completed below 20 seconds' => $totalElapsed < 20,
];
$failed = false;
foreach ($checks as $label => $passes) {
    echo ($passes ? 'PASS ' : 'FAIL ') . $label . PHP_EOL;
    $failed = $failed || !$passes;
}
echo 'INFO context_seconds=' . number_format($contextElapsed, 3) . PHP_EOL;
echo 'INFO send_seconds=' . number_format($sendElapsed, 3) . PHP_EOL;
echo 'INFO total_seconds=' . number_format($totalElapsed, 3) . PHP_EOL;
if (!empty($delivery['message_id'])) {
    echo 'INFO telegram_message_id=' . (int) $delivery['message_id'] . PHP_EOL;
}
exit($failed ? 1 : 0);
