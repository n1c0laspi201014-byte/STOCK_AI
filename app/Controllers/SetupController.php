<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\AlertRuleRepository;
use App\Repositories\AutomationLogRepository;
use App\Repositories\SettingsRepository;
use App\Repositories\StockRepository;
use App\Services\MarketDataService;
use App\Services\PortfolioService;
use App\Services\WatchlistService;
use App\Support\Container;
use App\Support\Request;
use App\Support\View;

final class SetupController
{
    public function index(Request $request): string
    {
        $userId = (int) auth_user()['id'];
        $portfolio = Container::get(PortfolioService::class)->data($userId);
        $watchlist = Container::get(WatchlistService::class)->all($userId);
        $dashboardStocks = [];
        foreach (array_merge($portfolio['holdings'], $watchlist) as $stock) {
            $id = (int) $stock['stock_id'];
            $dashboardStocks[$id] = [
                'id' => $id,
                'symbol' => $stock['symbol'],
                'company_name' => $stock['company_name'],
            ];
        }
        return View::render('setup/index', [
            'pageTitle' => 'Setup', 'activePage' => 'setup', 'pageScript' => 'setup.js',
            'settings' => Container::get(SettingsRepository::class)->get($userId),
            'alerts' => Container::get(AlertRuleRepository::class)->all($userId),
            'stocks' => Container::get(StockRepository::class)->demoUniverse(),
            'dashboardStocks' => array_values($dashboardStocks),
            'automationLogs' => is_admin() ? Container::get(AutomationLogRepository::class)->recent(25) : [],
            'marketConfigured' => Container::get(MarketDataService::class)->providerConfigured(),
        ]);
    }
}
