<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class WatchlistRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function all(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT w.*, s.symbol, s.company_name, s.exchange_code, s.currency,
                    ps.price AS current_price, ps.previous_close, ps.provider, ps.provider_timestamp, ps.received_at, ps.is_delayed,
                    CASE WHEN h.quantity > 0 THEN 1 ELSE 0 END AS is_owned
             FROM watchlist_items w
             JOIN stocks s ON s.id = w.stock_id
             LEFT JOIN portfolios p ON p.user_id = w.user_id
             LEFT JOIN holdings h ON h.portfolio_id = p.id AND h.stock_id = s.id AND h.quantity > 0
             LEFT JOIN price_snapshots ps ON ps.id = (SELECT p2.id FROM price_snapshots p2 WHERE p2.stock_id = s.id ORDER BY p2.received_at DESC LIMIT 1)
             WHERE w.user_id = :user_id ORDER BY w.added_at DESC'
        );
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll();
    }

    public function add(int $userId, int $stockId): bool
    {
        $statement = $this->pdo->prepare('INSERT IGNORE INTO watchlist_items (user_id, stock_id) VALUES (:user_id, :stock_id)');
        $statement->execute(['user_id' => $userId, 'stock_id' => $stockId]);
        return $statement->rowCount() > 0;
    }

    public function remove(int $userId, int $stockId): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM watchlist_items WHERE user_id = :user_id AND stock_id = :stock_id');
        $statement->execute(['user_id' => $userId, 'stock_id' => $stockId]);
        return $statement->rowCount() > 0;
    }

    public function update(int $userId, int $stockId, ?string $note, ?float $target): bool
    {
        $statement = $this->pdo->prepare('UPDATE watchlist_items SET note = :note, target_buy_price = :target WHERE user_id = :user_id AND stock_id = :stock_id');
        $statement->execute(['note' => $note, 'target' => $target, 'user_id' => $userId, 'stock_id' => $stockId]);
        return $statement->rowCount() > 0;
    }

    public function contains(int $userId, int $stockId): bool
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM watchlist_items WHERE user_id = :user_id AND stock_id = :stock_id');
        $statement->execute(['user_id' => $userId, 'stock_id' => $stockId]);
        return (bool) $statement->fetchColumn();
    }
}

