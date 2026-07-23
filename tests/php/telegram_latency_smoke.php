<?php
declare(strict_types=1);

use App\Config\Database;
use App\Services\TelegramQuestionService;
use App\Support\Container;

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';

$pdo = Database::connection();
$connection = $pdo->query(
    "SELECT u.id, tc.chat_id
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

$stock = $pdo->query("SELECT id FROM stocks WHERE symbol='NVDA' ORDER BY id LIMIT 1")->fetch();
$predictionCountBefore = $stock
    ? (int) $pdo->query('SELECT COUNT(*) FROM predictions WHERE user_id=' . (int) $connection['id'] . ' AND stock_id=' . (int) $stock['id'])->fetchColumn()
    : 0;

$service = Container::get(TelegramQuestionService::class);
$started = microtime(true);
$stockAnswer = $service->answer((string) $connection['chat_id'], '/stock NVDA');
$stockElapsed = microtime(true) - $started;
$stockText = (string) ($stockAnswer['message'] ?? '');

$started = microtime(true);
$newsAnswer = $service->answer((string) $connection['chat_id'], "what's new with NVIDIA?");
$newsElapsed = microtime(true) - $started;
$newsText = (string) ($newsAnswer['message'] ?? '');

$predictionCountAfter = $stock
    ? (int) $pdo->query('SELECT COUNT(*) FROM predictions WHERE user_id=' . (int) $connection['id'] . ' AND stock_id=' . (int) $stock['id'])->fetchColumn()
    : 0;

$checks = [
    'Fast command resolves NVDA' => ($stockAnswer['symbol'] ?? null) === 'NVDA',
    'Fast command includes current quote timestamp' => str_contains($stockText, 'Current price:') && str_contains($stockText, 'Updated:'),
    'Fast command WAMP context is below 20 seconds' => $stockElapsed < 20,
    'Natural news question resolves NVDA' => ($newsAnswer['symbol'] ?? null) === 'NVDA',
    'Natural news context is explicitly limited to seven days' => str_contains($newsText, 'last 7 days'),
    'Natural news WAMP context is below 20 seconds' => $newsElapsed < 20,
    'Questions reuse saved predictions instead of generating new AI work' => $predictionCountAfter === $predictionCountBefore,
];

$failed = false;
foreach ($checks as $label => $passes) {
    echo ($passes ? 'PASS ' : 'FAIL ') . $label . PHP_EOL;
    $failed = $failed || !$passes;
}
echo 'INFO fast_context_seconds=' . number_format($stockElapsed, 3) . PHP_EOL;
echo 'INFO news_context_seconds=' . number_format($newsElapsed, 3) . PHP_EOL;
exit($failed ? 1 : 0);
