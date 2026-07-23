<?php
declare(strict_types=1);

$root = dirname(__DIR__);

require_once $root . '/app/Config/Env.php';
\App\Config\Env::load($root . '/.env');

$composer = $root . '/vendor/autoload.php';
if (is_file($composer)) {
    require_once $composer;
} else {
    spl_autoload_register(static function (string $class) use ($root): void {
        if (!str_starts_with($class, 'App\\')) {
            return;
        }
        $file = $root . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    });
    require_once $root . '/app/Support/Helpers.php';
}

\App\Config\AppConfig::load($root . '/config');
date_default_timezone_set((string) config('app.timezone', 'UTC'));

if (PHP_SAPI === 'cli') {
    $_SESSION ??= [];
} elseif (session_status() !== PHP_SESSION_ACTIVE) {
    $sessionPath = session_save_path();
    if ($sessionPath === '' || !is_dir($sessionPath) || !is_writable($sessionPath)) {
        session_save_path($root . '/storage/cache');
    }
    session_name((string) config('app.session_name', 'papertrade_session'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

return require $root . '/config/routes.php';
