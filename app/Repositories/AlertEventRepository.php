<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AlertEventRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function recent(int $userId, int $limit = 5): array
    {
        $statement = $this->pdo->prepare('SELECT e.*, s.symbol FROM alert_events e JOIN stocks s ON s.id = e.stock_id WHERE e.user_id = :user_id ORDER BY e.triggered_at DESC LIMIT ' . max(1, min(100, $limit)));
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll();
    }

    public function countToday(int $userId): int
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM alert_events WHERE user_id = :user_id AND DATE(triggered_at) = CURDATE()');
        $statement->execute(['user_id' => $userId]);
        return (int) $statement->fetchColumn();
    }

    public function create(array $event): int
    {
        $statement = $this->pdo->prepare('INSERT INTO alert_events (alert_rule_id, user_id, stock_id, reference_price, current_price, change_amount, change_percent, prediction_id, urgency, message, telegram_status) VALUES (:alert_rule_id, :user_id, :stock_id, :reference_price, :current_price, :change_amount, :change_percent, :prediction_id, :urgency, :message, "pending")');
        $statement->execute($event);
        return (int) $this->pdo->lastInsertId();
    }

    public function telegramResult(int $id, bool $sent, ?string $error = null): void
    {
        $statement = $this->pdo->prepare('UPDATE alert_events SET telegram_status = :status, telegram_error = :error, sent_at = IF(:sent = 1, NOW(), sent_at) WHERE id = :id');
        $statement->execute(['status' => $sent ? 'sent' : 'failed', 'error' => $error, 'sent' => $sent ? 1 : 0, 'id' => $id]);
    }
}

