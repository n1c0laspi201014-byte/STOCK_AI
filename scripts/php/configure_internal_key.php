<?php
declare(strict_types=1);

$envPath = dirname(__DIR__, 2) . '/.env';
if (!is_file($envPath) || !is_readable($envPath) || !is_writable($envPath)) {
    fwrite(STDERR, "FAIL .env must exist and be readable/writable.\n");
    exit(1);
}

$content = (string) file_get_contents($envPath);
$pattern = '/^INTERNAL_N8N_API_KEY=(.*)$/m';
$current = '';
if (preg_match($pattern, $content, $match) === 1) {
    $current = trim((string) $match[1], " \t\n\r\0\x0B\"'");
}

if ($current !== '' && $current !== 'replace-with-a-long-random-value' && strlen($current) >= 32) {
    echo "PASS Internal n8n API key already configured.\n";
    exit(0);
}

$key = bin2hex(random_bytes(32));
$replacement = 'INTERNAL_N8N_API_KEY=' . $key;
$count = 0;
$updated = preg_replace($pattern, $replacement, $content, 1, $count);
if (!is_string($updated)) {
    fwrite(STDERR, "FAIL Could not update .env content.\n");
    exit(1);
}
if ($count === 0) {
    $updated = rtrim($updated) . PHP_EOL . $replacement . PHP_EOL;
}

if (file_put_contents($envPath, $updated, LOCK_EX) === false) {
    fwrite(STDERR, "FAIL Could not write .env.\n");
    exit(1);
}

echo "PASS Generated and stored a private 64-character internal n8n API key.\n";
