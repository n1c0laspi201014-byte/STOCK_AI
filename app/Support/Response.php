<?php
declare(strict_types=1);

namespace App\Support;

final class Response
{
    public static function json(array $payload, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    public static function redirect(string $location, int $status = 302): never
    {
        header('Location: ' . $location, true, $status);
        exit;
    }

    public static function status(int $status): void
    {
        http_response_code($status);
    }
}

