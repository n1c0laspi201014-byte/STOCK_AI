<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$failed = false;

function check(string $label, bool $passes, string $tested, string $fix): void
{
    global $failed;
    echo ($passes ? 'PASS ' : 'FAIL ') . $label . PHP_EOL;
    echo '  Tested: ' . $tested . PHP_EOL;
    if (!$passes) {
        echo '  Likely fix: ' . $fix . PHP_EOL;
        $failed = true;
    }
}

check('PHP version', version_compare(PHP_VERSION, '8.0.0', '>='), 'PHP 8.0 or later; found ' . PHP_VERSION, 'Select PHP 8.x in WAMP64, then restart WAMP.');
foreach (['curl', 'json', 'openssl', 'PDO', 'pdo_mysql', 'mbstring'] as $extension) {
    check("PHP extension {$extension}", extension_loaded($extension), "extension_loaded('{$extension}')", "Enable php_{$extension} in WAMP > PHP > PHP extensions, then restart Apache. Check the active php.ini.");
}
check('Environment example', is_file($root . '/.env.example'), '.env.example exists', 'Restore .env.example from the project.');
check('Local environment', is_file($root . '/.env'), '.env exists and is ignored by Git', 'Copy .env.example to .env; scripts/windows/02-create-local-env.bat does this safely.');
check('Storage cache writable', is_dir($root . '/storage/cache') && is_writable($root . '/storage/cache'), 'storage/cache exists and PHP can write it', 'Grant the Apache/PHP user write access to storage/cache.');
check('Storage logs writable', is_dir($root . '/storage/logs') && is_writable($root . '/storage/logs'), 'storage/logs exists and PHP can write it', 'Grant the Apache/PHP user write access to storage/logs.');
check('Front controller', is_file($root . '/public/index.php') && is_file($root . '/public/.htaccess'), 'public/index.php and public/.htaccess exist', 'Restore both files and enable Apache mod_rewrite plus AllowOverride All.');
check('Database schema', is_file($root . '/database/schema.sql'), 'database/schema.sql exists', 'Restore database/schema.sql.');

echo $failed ? "FAIL Requirements need attention.\n" : "PASS Requirements checker completed.\n";
exit($failed ? 1 : 0);

