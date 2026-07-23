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

$answer = Container::get(TelegramQuestionService::class)->answer(
    (string) $connection['chat_id'],
    'NVIDIA'
);

$context = [
    'chat_id' => (string) $connection['chat_id'],
    'user_id' => (int) $connection['id'],
    'symbol' => $answer['symbol'] ?? null,
    'question' => 'How is NVIDIA doing, what does the prediction mean, and what should I consider?',
    'factual_answer' => (string) ($answer['message'] ?? ''),
];

echo json_encode(
    $context,
    JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
);
