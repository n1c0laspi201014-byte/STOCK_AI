<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$root = dirname(__DIR__);
require_once $root . '/app/Config/Env.php';
\App\Config\Env::load($root . '/.env');

$checks = [
    'php' => ['status' => version_compare(PHP_VERSION, '8.0.0', '>='), 'detail' => PHP_VERSION],
    'curl' => ['status' => extension_loaded('curl'), 'detail' => extension_loaded('curl') ? 'loaded' : 'missing'],
    'pdo_mysql' => ['status' => extension_loaded('pdo_mysql'), 'detail' => extension_loaded('pdo_mysql') ? 'loaded' : 'missing'],
    'storage_writable' => ['status' => is_writable($root . '/storage'), 'detail' => is_writable($root . '/storage') ? 'writable' : 'not writable'],
];

$databaseOk = false;
try {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', getenv('DB_HOST') ?: '127.0.0.1', getenv('DB_PORT') ?: '3306', getenv('DB_DATABASE') ?: 'stockdata');
    $pdo = new PDO($dsn, getenv('DB_USERNAME') ?: 'root', getenv('DB_PASSWORD') ?: '', [PDO::ATTR_TIMEOUT => 2]);
    $databaseOk = (bool) $pdo->query('SELECT 1')->fetchColumn();
    $databaseDetail = $databaseOk ? 'connected to stockdata' : 'query failed';
} catch (Throwable $exception) {
    $databaseDetail = 'not connected; run scripts/php/setup_database.php';
}
$checks['database'] = ['status' => $databaseOk, 'detail' => $databaseDetail];

$healthy = !in_array(false, array_column($checks, 'status'), true);
http_response_code($healthy ? 200 : 503);
echo json_encode([
    'application' => 'STOCK AI',
    'status' => $healthy ? 'healthy' : 'setup_required',
    'checks' => $checks,
    'timestamp' => gmdate(DATE_ATOM),
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
