<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\PredictionRepository;
use App\Services\PortfolioService;
use App\Services\WatchlistService;
use App\Support\Container;
use App\Support\Request;
use App\Support\View;

final class StocksController
{
    public function index(Request $request): string
    {
        $userId = (int) auth_user()['id'];
        return View::render('stocks/index', [
            'pageTitle' => 'Stocks', 'activePage' => 'stocks', 'pageScript' => 'stocks.js',
            'portfolio' => Container::get(PortfolioService::class)->data($userId),
            'watchlist' => Container::get(WatchlistService::class)->all($userId),
            'predictions' => Container::get(PredictionRepository::class)->history($userId, null, 100),
            'initialTab' => $request->input('tab', 'owned'),
        ]);
    }
}

