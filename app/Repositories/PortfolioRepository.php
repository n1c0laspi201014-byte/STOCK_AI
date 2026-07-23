<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class PortfolioRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function summary(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT p.id, p.name, p.base_currency, us.starting_cash, us.current_cash, us.allow_fractional_shares, us.default_fee,
                    COALESCE(SUM(h.total_cost), 0) AS total_invested_cost,
                    COALESCE(SUM(h.realized_profit_loss), 0) AS realized_profit_loss,
                    COUNT(CASE WHEN h.quantity > 0 THEN 1 END) AS owned_count
             FROM portfolios p
             JOIN user_settings us ON us.user_id = p.user_id
             LEFT JOIN holdings h ON h.portfolio_id = p.id
             WHERE p.user_id = :user_id
             GROUP BY p.id, p.name, p.base_currency, us.starting_cash, us.current_cash, us.allow_fractional_shares, us.default_fee'
        );
        $statement->execute(['user_id' => $userId]);
        return $statement->fetch() ?: [];
    }

    public function holdings(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT h.*, s.symbol, s.company_name, s.exchange_code, s.currency,
                    ps.price AS current_price, ps.previous_close, ps.provider, ps.provider_timestamp, ps.received_at, ps.is_delayed,
                    CASE WHEN ps.price IS NULL THEN NULL ELSE h.quantity * ps.price END AS market_value,
                    CASE WHEN ps.price IS NULL THEN NULL ELSE (h.quantity * ps.price) - h.total_cost END AS unrealized_profit_loss
             FROM holdings h
             JOIN portfolios p ON p.id = h.portfolio_id AND p.user_id = :user_id
             JOIN stocks s ON s.id = h.stock_id
             LEFT JOIN price_snapshots ps ON ps.id = (SELECT p2.id FROM price_snapshots p2 WHERE p2.stock_id = s.id ORDER BY p2.received_at DESC LIMIT 1)
             WHERE h.quantity > 0
             ORDER BY market_value DESC, s.symbol'
        );
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll();
    }

    public function holding(int $userId, int $stockId): ?array
    {
        $statement = $this->pdo->prepare('SELECT h.* FROM holdings h JOIN portfolios p ON p.id = h.portfolio_id WHERE p.user_id = :user_id AND h.stock_id = :stock_id LIMIT 1');
        $statement->execute(['user_id' => $userId, 'stock_id' => $stockId]);
        return $statement->fetch() ?: null;
    }

    public function watchlistCount(int $userId): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM watchlist_items WHERE user_id = :user_id');
        $statement->execute(['user_id' => $userId]);
        return (int) $statement->fetchColumn();
    }
}

