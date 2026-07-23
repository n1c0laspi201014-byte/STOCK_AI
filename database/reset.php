<?php
declare(strict_types=1);

use App\Config\Database;

require_once dirname(__DIR__) . '/bootstrap/app.php';

if (PHP_SAPI !== 'cli' || !in_array('--confirm-demo-reset', $argv ?? [], true)) {
    fwrite(STDERR, "FAIL Reset not run. Use: php database/reset.php --confirm-demo-reset\n");
    exit(1);
}

try {
    $pdo = Database::connection();
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    foreach (['prediction_outcomes','alert_events','predictions','alert_rules','watchlist_items','holdings','transactions','price_snapshots','telegram_connections','dashboard_preferences','automation_logs','portfolios','user_settings','users','stocks'] as $table) {
        $pdo->exec("TRUNCATE TABLE `{$table}`");
    }
    $pdo->exec("ALTER TABLE users MODIFY role ENUM('admin', 'trader') NOT NULL DEFAULT 'trader'");
    $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
} catch (Throwable $exception) {
    fwrite(STDERR, "FAIL Demo reset\n  Tested: explicit reset confirmation and stockdata access\n  Likely fix: start WAMP MySQL and verify .env.\n  Error: {$exception->getMessage()}\n");
    exit(1);
}
require __DIR__ . '/seed.php';
