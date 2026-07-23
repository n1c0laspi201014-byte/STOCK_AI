<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$failed = false;
function contractCheck(string $label, bool $passes, string $detail = ''): void { global $failed; echo ($passes ? 'PASS ' : 'FAIL ') . $label . ($detail !== '' ? " — {$detail}" : '') . PHP_EOL; $failed = $failed || !$passes; }

$required = [
    'public/index.php','public/health.php','public/.htaccess','database/schema.sql','database/seed.php','database/reset.php','app/Integrations/MarketData/YahooFinanceHistoryProvider.php','app/Services/TelegramQuestionService.php','app/Views/auth/register.php',
    'docker-compose.n8n.yml','SETUP_HANDOFF.md','TEAM_STATUS.md','DEMO_SCRIPT.md','IMPLEMENTATION_STATUS.md','.env.example','.gitignore',
    'scripts/windows/01-check-requirements.bat','scripts/windows/02-create-local-env.bat','scripts/windows/03-test-application.bat','scripts/windows/04-test-integrations.bat',
    'scripts/php/check_requirements.php','scripts/php/setup_database.php','scripts/php/test_market_api.php','scripts/php/test_openrouter.php','scripts/php/test_internal_api.php','scripts/php/create_demo_data.php',
    'n8n/workflows/papertrade-telegram-hub.json','tests/php/automation_hub_smoke.php','tests/php/telegram_company_resolution_smoke.php',
];
$missing = array_values(array_filter($required, static fn(string $file): bool => !is_file($root . '/' . $file)));
contractCheck('Required automatic-generation files', $missing === [], $missing === [] ? count($required) . ' files present' : 'missing: ' . implode(', ', $missing));

$env = file_get_contents($root . '/.env.example');
contractCheck('Main database name is stockdata', str_contains($env, 'DB_DATABASE=stockdata'));
contractCheck('.env ignored by Git', preg_match('/^\.env$/m', file_get_contents($root . '/.gitignore')) === 1);

$schema = file_get_contents($root . '/database/schema.sql');
$tables = ['users','user_settings','stocks','portfolios','transactions','holdings','watchlist_items','price_snapshots','predictions','prediction_outcomes','alert_rules','alert_events','telegram_connections','dashboard_preferences','automation_logs'];
$missingTables = array_values(array_filter($tables, static fn(string $table): bool => !preg_match('/CREATE TABLE IF NOT EXISTS ' . preg_quote($table, '/') . '\s*\(/i', $schema)));
contractCheck('All required database tables', $missingTables === [], $missingTables === [] ? count($tables) . ' tables present' : 'missing: ' . implode(', ', $missingTables));
contractCheck('Exactly Admin and Trader roles', str_contains($schema, "role ENUM('admin', 'trader')") && !str_contains($schema, "'analyst'"));
contractCheck('Money and quantities use DECIMAL', substr_count(strtoupper($schema), 'DECIMAL(') >= 20);
contractCheck('InnoDB and utf8mb4 configured', str_contains($schema, 'ENGINE=InnoDB') && str_contains($schema, 'utf8mb4_unicode_ci'));

$navbar = file_get_contents($root . '/app/Views/partials/navbar.php');
contractCheck('Navbar contains exact four page labels', preg_match("/\['dashboard' => 'Dashboard', 'stocks' => 'Stocks', 'predictions' => 'Predictions', 'setup' => 'Setup'\]/", $navbar) === 1);
contractCheck('Logout is POST with CSRF', str_contains($navbar, 'method="post"') && str_contains($navbar, 'Csrf::token()'));

$routes = file_get_contents($root . '/config/routes.php');
foreach (['/register','/dashboard','/stocks','/predictions','/setup','/api/portfolio/buy','/api/portfolio/sell','/api/watchlist','/api/internal/automation/due-alert-rules','/api/internal/automation/telegram-stock-questions'] as $route) contractCheck("Route {$route}", str_contains($routes, "'{$route}'"));
contractCheck('Mutations use CSRF middleware group', str_contains($routes, '$mutation = [AuthMiddleware::class, CsrfMiddleware::class]'));
contractCheck('Internal routes use API-key middleware', str_contains($routes, '$internal = [InternalApiMiddleware::class]'));

$users = file_get_contents($root . '/app/Repositories/UserRepository.php');
contractCheck('Registration always creates Trader', str_contains($users, 'password_hash, role, is_active) VALUES (:name, :email, :password_hash, "trader", 1)'));

foreach (glob($root . '/n8n/workflows/*.json') ?: [] as $workflow) contractCheck('Workflow JSON ' . basename($workflow), is_array(json_decode((string) file_get_contents($workflow), true)));
contractCheck('Exactly one current n8n workflow export', count(glob($root . '/n8n/workflows/*.json') ?: []) === 1);

$publicText = '';
foreach (glob($root . '/public/assets/js/*.js') ?: [] as $file) $publicText .= file_get_contents($file);
contractCheck('Browser JavaScript has no secret names', !preg_match('/OPENROUTER_API_KEY|TELEGRAM_BOT_TOKEN|INTERNAL_N8N_API_KEY|DB_PASSWORD/', $publicText));

exit($failed ? 1 : 0);
