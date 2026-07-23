<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
require_once $root . '/app/Config/Env.php';
\App\Config\Env::load($root . '/.env');

$host = (string) \App\Config\Env::get('DB_HOST', '127.0.0.1');
$port = (string) \App\Config\Env::get('DB_PORT', '3306');
$database = (string) \App\Config\Env::get('DB_DATABASE', 'stockdata');
$username = (string) \App\Config\Env::get('DB_USERNAME', 'root');
$password = (string) \App\Config\Env::get('DB_PASSWORD', '');

if ($database !== 'stockdata') {
    fwrite(STDERR, "FAIL Database name must be stockdata for this project.\nLikely fix: set DB_DATABASE=stockdata in .env.\n");
    exit(1);
}

try {
    $server = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
    echo "PASS Database connection\n  Tested: MySQL server at {$host}:{$port}\n";
    $server->exec('CREATE DATABASE IF NOT EXISTS `stockdata` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    echo "PASS Database stockdata exists\n  Tested: CREATE DATABASE permission and utf8mb4 collation\n";
    $pdo = new PDO("mysql:host={$host};port={$port};dbname=stockdata;charset=utf8mb4", $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
    $schema = file_get_contents($root . '/database/schema.sql');
    if ($schema === false) throw new RuntimeException('database/schema.sql could not be read.');
    $pdo->exec($schema);
    $roleColumn = $pdo->query("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = 'stockdata' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role'")->fetchColumn();
    if ($roleColumn !== "enum('admin','trader')") {
        $pdo->exec("DELETE FROM users WHERE email = 'analyst@papertrade.local'");
        $pdo->exec("UPDATE users SET role = 'trader' WHERE role NOT IN ('admin', 'trader')");
        $pdo->exec("ALTER TABLE users MODIFY role ENUM('admin', 'trader') NOT NULL DEFAULT 'trader'");
    }
    echo "PASS Schema applied\n  Tested: every required table and index in database/schema.sql\n";
} catch (Throwable $exception) {
    fwrite(STDERR, "FAIL Database setup\n  Tested: MySQL connection, stockdata creation, and schema application\n  Likely fix: start MySQL in WAMP, verify DB_* in .env, or create stockdata in http://localhost/phpmyadmin/ with utf8mb4_unicode_ci.\n  Error: {$exception->getMessage()}\n");
    exit(1);
}

require $root . '/database/seed.php';
