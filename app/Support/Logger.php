<?php
declare(strict_types=1);

namespace App\Support;

final class Logger
{
    public static function write(string $level, string $message, array $context = []): void
    {
        $masked = [];
        foreach ($context as $key => $value) {
            $masked[$key] = preg_match('/password|secret|token|key|authorization|session/i', (string) $key) ? '[REDACTED]' : $value;
        }
        $line = json_encode([
            'time' => gmdate(DATE_ATOM),
            'level' => strtoupper($level),
            'message' => $message,
            'context' => $masked,
        ], JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE) . PHP_EOL;
        @file_put_contents(base_path('storage/logs/app-' . date('Y-m-d') . '.log'), $line, FILE_APPEND | LOCK_EX);
    }
}

