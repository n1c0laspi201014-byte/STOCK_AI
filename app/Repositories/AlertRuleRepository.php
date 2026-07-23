<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AlertRuleRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function all(int $userId): array
    {
        $statement = $this->pdo->prepare('SELECT a.*, s.symbol, s.company_name FROM alert_rules a JOIN stocks s ON s.id = a.stock_id WHERE a.user_id = :user_id ORDER BY a.created_at DESC');
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll();
    }

    public function due(): array
    {
        $statement = $this->pdo->query(
            'SELECT a.*, s.symbol, s.exchange_code, s.currency, tc.chat_id, us.timezone, us.quiet_hours_enabled, us.quiet_hours_start, us.quiet_hours_end, us.max_alerts_per_day
             FROM alert_rules a JOIN stocks s ON s.id = a.stock_id JOIN user_settings us ON us.user_id = a.user_id
             LEFT JOIN telegram_connections tc ON tc.user_id = a.user_id AND tc.is_enabled = 1
             WHERE a.is_enabled = 1 AND (a.last_checked_at IS NULL OR a.last_checked_at <= DATE_SUB(NOW(), INTERVAL a.check_interval_minutes MINUTE))'
        );
        return $statement->fetchAll();
    }

    public function findOwned(int $userId, int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT a.*, s.symbol, s.currency FROM alert_rules a JOIN stocks s ON s.id = a.stock_id WHERE a.user_id = :user_id AND a.id = :id');
        $statement->execute(['user_id' => $userId, 'id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function save(int $userId, array $data, ?int $id = null): int
    {
        $params = [
            'user_id' => $userId,
            'stock_id' => (int) $data['stock_id'],
            'name' => trim((string) $data['name']),
            'is_enabled' => !empty($data['is_enabled']) ? 1 : 0,
            'threshold_type' => $data['threshold_type'],
            'threshold_value' => $data['threshold_value'],
            'direction' => $data['direction'],
            'reference_type' => $data['reference_type'],
            'reference_price' => $data['reference_price'] ?? null,
            'check_interval_minutes' => (int) ($data['check_interval_minutes'] ?? 5),
            'cooldown_minutes' => (int) ($data['cooldown_minutes'] ?? 30),
            'market_hours_only' => !empty($data['market_hours_only']) ? 1 : 0,
            'ai_commentary_enabled' => !empty($data['ai_commentary_enabled']) ? 1 : 0,
            'minimum_confidence' => (float) ($data['minimum_confidence'] ?? 0),
        ];
        if ($id === null) {
            $statement = $this->pdo->prepare('INSERT INTO alert_rules (user_id, stock_id, name, is_enabled, threshold_type, threshold_value, direction, reference_type, reference_price, check_interval_minutes, cooldown_minutes, market_hours_only, ai_commentary_enabled, minimum_confidence) VALUES (:user_id, :stock_id, :name, :is_enabled, :threshold_type, :threshold_value, :direction, :reference_type, :reference_price, :check_interval_minutes, :cooldown_minutes, :market_hours_only, :ai_commentary_enabled, :minimum_confidence)');
            $statement->execute($params);
            return (int) $this->pdo->lastInsertId();
        }
        $params['id'] = $id;
        $statement = $this->pdo->prepare('UPDATE alert_rules SET stock_id=:stock_id, name=:name, is_enabled=:is_enabled, threshold_type=:threshold_type, threshold_value=:threshold_value, direction=:direction, reference_type=:reference_type, reference_price=:reference_price, check_interval_minutes=:check_interval_minutes, cooldown_minutes=:cooldown_minutes, market_hours_only=:market_hours_only, ai_commentary_enabled=:ai_commentary_enabled, minimum_confidence=:minimum_confidence WHERE id=:id AND user_id=:user_id');
        $statement->execute($params);
        return $id;
    }

    public function delete(int $userId, int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM alert_rules WHERE id = :id AND user_id = :user_id');
        $statement->execute(['id' => $id, 'user_id' => $userId]);
        return $statement->rowCount() > 0;
    }
}

