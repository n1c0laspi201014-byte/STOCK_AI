<?php
declare(strict_types=1);

use App\Config\AppConfig;

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return AppConfig::get($key, $default);
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        return rtrim((string) config('app.url', ''), '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $relative = ltrim($path, '/');
        $file = base_path('public/assets/' . $relative);
        $version = is_file($file) ? '?v=' . filemtime($file) : '';
        return url('assets/' . $relative) . $version;
    }
}

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('money')) {
    function money(float|int|string $value, string $currency = 'USD'): string
    {
        return $currency . ' ' . number_format((float) $value, 2, '.', ',');
    }
}

if (!function_exists('percent')) {
    function percent(float|int|string $value): string
    {
        $number = (float) $value;
        return ($number > 0 ? '+' : '') . number_format($number, 2) . '%';
    }
}

if (!function_exists('auth_user')) {
    function auth_user(): ?array
    {
        return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return (auth_user()['role'] ?? null) === 'admin';
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        $root = dirname(__DIR__, 2);
        return $path === '' ? $root : $root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }
}

if (!function_exists('secure_curl_options')) {
    /**
     * Attach the project CA bundle when present. This keeps TLS verification
     * enabled on WAMP installations whose Apache PHP has no default CA file.
     */
    function secure_curl_options(array $options): array
    {
        $caBundle = base_path('config/cacert.pem');
        if (is_file($caBundle) && is_readable($caBundle)) {
            $options[CURLOPT_CAINFO] = $caBundle;
        }
        return $options;
    }
}
