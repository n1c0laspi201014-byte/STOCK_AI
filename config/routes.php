<?php
declare(strict_types=1);

use App\Controllers\Api\AutomationApiController;
use App\Controllers\Api\MarketApiController;
use App\Controllers\Api\PortfolioApiController;
use App\Controllers\Api\PredictionApiController;
use App\Controllers\Api\SettingsApiController;
use App\Controllers\Api\WatchlistApiController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\PredictionsController;
use App\Controllers\SetupController;
use App\Controllers\StocksController;
use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\InternalApiMiddleware;
use App\Support\Response;
use App\Support\Router;

$router = new Router();
$auth = [AuthMiddleware::class];
$mutation = [AuthMiddleware::class, CsrfMiddleware::class];
$internal = [InternalApiMiddleware::class];

$router->get('/', static fn() => Response::redirect(auth_user() ? url('/dashboard') : url('/login')));
$router->get('/login', [AuthController::class, 'showLogin'], [GuestMiddleware::class]);
$router->post('/login', [AuthController::class, 'login'], [GuestMiddleware::class, CsrfMiddleware::class]);
$router->get('/register', [AuthController::class, 'showRegister'], [GuestMiddleware::class]);
$router->post('/register', [AuthController::class, 'register'], [GuestMiddleware::class, CsrfMiddleware::class]);
$router->post('/logout', [AuthController::class, 'logout'], $mutation);

$router->get('/dashboard', [DashboardController::class, 'index'], $auth);
$router->get('/stocks', [StocksController::class, 'index'], $auth);
$router->get('/predictions', [PredictionsController::class, 'index'], $auth);
$router->get('/setup', [SetupController::class, 'index'], $auth);

$router->get('/api/market/search', [MarketApiController::class, 'search'], $auth);
$router->get('/api/market/quote', [MarketApiController::class, 'quote'], $auth);
$router->get('/api/market/history', [MarketApiController::class, 'history'], $auth);
$router->get('/api/market/profile', [MarketApiController::class, 'profile'], $auth);
$router->get('/api/market/news', [MarketApiController::class, 'news'], $auth);
$router->get('/api/market/status', [MarketApiController::class, 'status'], $auth);
$router->post('/api/market/refresh', [MarketApiController::class, 'refresh'], $mutation);

$router->get('/api/portfolio', [PortfolioApiController::class, 'show'], $auth);
$router->get('/api/portfolio/transactions', [PortfolioApiController::class, 'transactions'], $auth);
$router->post('/api/portfolio/buy', [PortfolioApiController::class, 'buy'], $mutation);
$router->post('/api/portfolio/sell', [PortfolioApiController::class, 'sell'], $mutation);
$router->post('/api/portfolio/reset', [PortfolioApiController::class, 'reset'], $mutation);

$router->get('/api/watchlist', [WatchlistApiController::class, 'index'], $auth);
$router->post('/api/watchlist', [WatchlistApiController::class, 'store'], $mutation);
$router->patch('/api/watchlist/{stockId}', [WatchlistApiController::class, 'update'], $mutation);
$router->delete('/api/watchlist/{stockId}', [WatchlistApiController::class, 'destroy'], $mutation);

$router->get('/api/predictions/owned', [PredictionApiController::class, 'owned'], $auth);
$router->get('/api/predictions/watchlist', [PredictionApiController::class, 'watchlist'], $auth);
$router->get('/api/predictions/discovery', [PredictionApiController::class, 'discovery'], $auth);
$router->get('/api/predictions/history', [PredictionApiController::class, 'history'], $auth);
$router->post('/api/predictions/generate', [PredictionApiController::class, 'generate'], $mutation);
$router->post('/api/predictions/discover', [PredictionApiController::class, 'discover'], $mutation);

$router->get('/api/settings', [SettingsApiController::class, 'index'], $auth);
$router->patch('/api/settings/profile', [SettingsApiController::class, 'profile'], $mutation);
$router->patch('/api/settings/dashboard', [SettingsApiController::class, 'dashboard'], $mutation);
$router->patch('/api/settings/paper-account', [SettingsApiController::class, 'paperAccount'], $mutation);
$router->patch('/api/settings/ai', [SettingsApiController::class, 'ai'], $mutation);
$router->post('/api/settings/telegram/verify', [SettingsApiController::class, 'telegramVerify'], $mutation);
$router->post('/api/settings/telegram/test', [SettingsApiController::class, 'telegramTest'], $mutation);
$router->get('/api/settings/alerts', [SettingsApiController::class, 'alerts'], $auth);
$router->post('/api/settings/alerts', [SettingsApiController::class, 'storeAlert'], $mutation);
$router->patch('/api/settings/alerts/{id}', [SettingsApiController::class, 'updateAlert'], $mutation);
$router->delete('/api/settings/alerts/{id}', [SettingsApiController::class, 'deleteAlert'], $mutation);
$router->post('/api/settings/alerts/{id}/test', [SettingsApiController::class, 'testAlert'], $mutation);
$router->get('/api/settings/automation-logs', [SettingsApiController::class, 'logs'], $auth);
$router->post('/api/settings/automations/pause', [SettingsApiController::class, 'pause'], $mutation);
$router->post('/api/settings/automations/resume', [SettingsApiController::class, 'resume'], $mutation);

$router->get('/api/internal/automation/due-alert-rules', [AutomationApiController::class, 'dueAlertRules'], $internal);
$router->post('/api/internal/automation/evaluate-alert', [AutomationApiController::class, 'evaluateAlert'], $internal);
$router->get('/api/internal/automation/due-morning-reports', [AutomationApiController::class, 'dueMorningReports'], $internal);
$router->get('/api/internal/automation/due-close-reports', [AutomationApiController::class, 'dueCloseReports'], $internal);
$router->post('/api/internal/automation/report-data', [AutomationApiController::class, 'reportData'], $internal);
$router->post('/api/internal/automation/log', [AutomationApiController::class, 'log'], $internal);
$router->post('/api/internal/automation/telegram-result', [AutomationApiController::class, 'telegramResult'], $internal);
$router->get('/api/internal/automation/telegram-stock-questions', [AutomationApiController::class, 'telegramStockQuestions'], $internal);

return $router;
