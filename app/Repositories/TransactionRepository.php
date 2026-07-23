<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class TransactionRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function forUser(int $userId, int $limit = 50): array
    {
        $statement = $this->pdo->prepare(
            'SELECT t.*, s.symbol, s.company_name, s.currency FROM transactions t
             JOIN portfolios p ON p.id = t.portfolio_id AND p.user_id = :user_id
             JOIN stocks s ON s.id = t.stock_id
             ORDER BY t.executed_at DESC, t.id DESC LIMIT ' . max(1, min(200, $limit))
        );
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll();
    }

    public function recentForStock(int $userId, int $stockId, int $limit = 10): array
    {
        $statement = $this->pdo->prepare(
            'SELECT t.* FROM transactions t JOIN portfolios p ON p.id = t.portfolio_id
             WHERE p.user_id = :user_id AND t.stock_id = :stock_id ORDER BY t.executed_at DESC LIMIT ' . max(1, min(50, $limit))
        );
        $statement->execute(['user_id' => $userId, 'stock_id' => $stockId]);
        return $statement->fetchAll();
    }
}

