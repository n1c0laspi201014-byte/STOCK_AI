<?php
declare(strict_types=1);

namespace App\Config;

final class AppConfig
{
    private static array $config = [];

    public static function load(string $configDirectory): void
    {
        foreach (glob(rtrim($configDirectory, '/\\') . DIRECTORY_SEPARATOR . '*.php') ?: [] as $file) {
            $value = require $file;
            if (is_array($value)) {
                self::$config[basename($file, '.php')] = $value;
            }
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = self::$config;
        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }
}

