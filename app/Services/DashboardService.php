<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\AlertEventRepository;
use App\Repositories\SettingsRepository;

final class DashboardService
{
    public function __construct(private readonly PortfolioService $portfolio, private readonly AlertEventRepository $alerts, private readonly SettingsRepository $settings) {}

    public function data(int $userId): array
    {
        $portfolio = $this->portfolio->data($userId, true);
        $holdings = $portfolio['holdings'];
        usort($holdings, static fn(array $a, array $b): int => ((float) ($b['unrealized_profit_loss'] ?? 0)) <=> ((float) ($a['unrealized_profit_loss'] ?? 0)));
        $preference = $this->settings->get($userId);
        $importantIds = array_slice($preference['important_stock_ids'] ?? [], 0, (int) ($preference['max_important_stocks'] ?? 4));
        $important = array_values(array_filter($portfolio['holdings'], static fn(array $holding): bool => in_array((int) $holding['stock_id'], $importantIds, true)));
        return [
            'portfolio' => $portfolio,
            'portfolio_history' => $this->portfolio->valueHistory($userId, 30),
            'latest_transaction' => $portfolio['transactions'][0] ?? null,
            'best_performer' => $holdings[0] ?? null,
            'worst_performer' => $holdings !== [] ? end($holdings) : null,
            'important_stocks' => $important,
            'recent_alerts' => $this->alerts->recent($userId, 5),
            'preferences' => $preference,
        ];
    }
}
