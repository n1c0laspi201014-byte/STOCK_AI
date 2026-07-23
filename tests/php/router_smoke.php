<?php
declare(strict_types=1);

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/login';
$_SERVER['SCRIPT_NAME'] = '/index.php';

$router = require dirname(__DIR__, 2) . '/bootstrap/app.php';
ob_start();
$router->dispatch(new \App\Support\Request());
$html = (string) ob_get_clean();

$checks = [
    'Login heading' => str_contains($html, 'Sign in to your paper account'),
    'Two development accounts' => substr_count($html, 'data-demo-email=') === 2,
    'No Analyst account' => !str_contains($html, 'analyst@papertrade.local'),
    'CSRF field' => str_contains($html, 'name="_csrf"'),
    'No authenticated navbar on login' => !str_contains($html, 'primary-navigation'),
];
$failed = false;
foreach ($checks as $label => $passes) {
    echo ($passes ? 'PASS ' : 'FAIL ') . $label . PHP_EOL;
    $failed = $failed || !$passes;
}
exit($failed ? 1 : 0);
