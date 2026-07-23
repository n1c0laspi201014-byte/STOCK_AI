<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AutomationLogRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function add(string $workflow, string $status, ?int $userId, ?string $message, array $context = [], ?string $executionKey = null): int
    {
        $statement = $this->pdo->prepare('INSERT INTO automation_logs (workflow_name, execution_key, user_id, status, message, context, finished_at) VALUES (:workflow,:execution_key,:user_id,:status,:message,:context,IF(:finished=1,NOW(),NULL)) ON DUPLICATE KEY UPDATE status=VALUES(status),message=VALUES(message),context=VALUES(context),finished_at=VALUES(finished_at)');
        $statement->execute(['workflow' => $workflow, 'execution_key' => $executionKey, 'user_id' => $userId, 'status' => $status, 'message' => $message, 'context' => json_encode($context), 'finished' => $status === 'started' ? 0 : 1]);
        return (int) $this->pdo->lastInsertId();
    }

    public function recent(int $limit = 50): array
    {
        return $this->pdo->query('SELECT l.*, u.name AS user_name FROM automation_logs l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.started_at DESC LIMIT ' . max(1, min(200, $limit)))->fetchAll();
    }
}

