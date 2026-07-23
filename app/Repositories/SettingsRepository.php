<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class SettingsRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function get(int $userId): array
    {
        $statement = $this->pdo->prepare('SELECT u.id AS user_id, u.name, u.email, u.role, us.*, dp.primary_chart_type, dp.primary_chart_stock_id, dp.secondary_chart_type, dp.secondary_chart_stock_id, dp.important_stock_ids, dp.max_important_stocks, tc.chat_id, tc.telegram_username, tc.is_verified AS telegram_verified, tc.is_enabled AS telegram_enabled, tc.last_test_at, tc.last_test_status FROM users u JOIN user_settings us ON us.user_id = u.id LEFT JOIN dashboard_preferences dp ON dp.user_id = u.id LEFT JOIN telegram_connections tc ON tc.user_id = u.id WHERE u.id = :user_id');
        $statement->execute(['user_id' => $userId]);
        $row = $statement->fetch() ?: [];
        $row['important_stock_ids'] = json_decode((string) ($row['important_stock_ids'] ?? '[]'), true) ?: [];
        return $row;
    }

    public function updateProfile(int $userId, array $data): void
    {
        $statement = $this->pdo->prepare('UPDATE users SET name = :name WHERE id = :user_id');
        $statement->execute(['name' => trim((string) $data['name']), 'user_id' => $userId]);
        $statement = $this->pdo->prepare('UPDATE user_settings SET timezone = :timezone WHERE user_id = :user_id');
        $statement->execute(['timezone' => (string) $data['timezone'], 'user_id' => $userId]);
    }

    public function updatePaperAccount(int $userId, array $data): void
    {
        $statement = $this->pdo->prepare('UPDATE user_settings SET base_currency=:currency, allow_fractional_shares=:fractional, default_fee=:fee, morning_report_enabled=:morning, morning_report_time=:morning_time, market_close_report_enabled=:close_report, quiet_hours_enabled=:quiet, quiet_hours_start=:quiet_start, quiet_hours_end=:quiet_end WHERE user_id=:user_id');
        $statement->execute([
            'currency' => strtoupper((string) ($data['base_currency'] ?? 'USD')),
            'fractional' => !empty($data['allow_fractional_shares']) ? 1 : 0,
            'fee' => max(0, (float) ($data['default_fee'] ?? 0)),
            'morning' => !empty($data['morning_report_enabled']) ? 1 : 0,
            'morning_time' => (string) ($data['morning_report_time'] ?? '07:30:00'),
            'close_report' => !empty($data['market_close_report_enabled']) ? 1 : 0,
            'quiet' => !empty($data['quiet_hours_enabled']) ? 1 : 0,
            'quiet_start' => !empty($data['quiet_hours_start']) ? $data['quiet_hours_start'] : null,
            'quiet_end' => !empty($data['quiet_hours_end']) ? $data['quiet_hours_end'] : null,
            'user_id' => $userId,
        ]);
    }

    public function updateDashboard(int $userId, array $data): void
    {
        $statement = $this->pdo->prepare('INSERT INTO dashboard_preferences (user_id, primary_chart_type, primary_chart_stock_id, secondary_chart_type, secondary_chart_stock_id, important_stock_ids, max_important_stocks) VALUES (:user_id,:primary,:primary_stock,:secondary,:secondary_stock,:important,:maximum) ON DUPLICATE KEY UPDATE primary_chart_type=VALUES(primary_chart_type),primary_chart_stock_id=VALUES(primary_chart_stock_id),secondary_chart_type=VALUES(secondary_chart_type),secondary_chart_stock_id=VALUES(secondary_chart_stock_id),important_stock_ids=VALUES(important_stock_ids),max_important_stocks=VALUES(max_important_stocks)');
        $statement->execute([
            'user_id' => $userId,
            'primary' => $data['primary_chart_type'] ?? 'portfolio_value',
            'primary_stock' => $data['primary_chart_stock_id'] ?: null,
            'secondary' => $data['secondary_chart_type'] ?? 'portfolio_allocation',
            'secondary_stock' => $data['secondary_chart_stock_id'] ?: null,
            'important' => json_encode(array_slice(array_map('intval', $data['important_stock_ids'] ?? []), 0, 10)),
            'maximum' => max(1, min(8, (int) ($data['max_important_stocks'] ?? 4))),
        ]);
    }

    public function updateAi(int $userId, array $data): void
    {
        $statement = $this->pdo->prepare('UPDATE user_settings SET ai_model=:model, default_horizon=:horizon, prediction_refresh_hours=:hours, minimum_urgent_confidence=:confidence, news_analysis_enabled=:news, max_news_items=:max_news WHERE user_id=:user_id');
        $statement->execute([
            'model' => trim((string) $data['ai_model']),
            'horizon' => $data['default_horizon'],
            'hours' => max(1, min(168, (int) $data['prediction_refresh_hours'])),
            'confidence' => max(0, min(100, (float) $data['minimum_urgent_confidence'])),
            'news' => !empty($data['news_analysis_enabled']) ? 1 : 0,
            'max_news' => max(0, min(20, (int) $data['max_news_items'])),
            'user_id' => $userId,
        ]);
    }

    public function saveTelegram(int $userId, string $chatId, ?string $username, bool $enabled): void
    {
        $statement = $this->pdo->prepare('INSERT INTO telegram_connections (user_id, chat_id, telegram_username, is_enabled) VALUES (:user_id,:chat_id,:username,:enabled) ON DUPLICATE KEY UPDATE chat_id=VALUES(chat_id),telegram_username=VALUES(telegram_username),is_enabled=VALUES(is_enabled),is_verified=IF(chat_id=VALUES(chat_id),is_verified,0)');
        $statement->execute(['user_id' => $userId, 'chat_id' => $chatId, 'username' => $username, 'enabled' => $enabled ? 1 : 0]);
    }

    public function markTelegramTest(int $userId, bool $success): void
    {
        $statement = $this->pdo->prepare('UPDATE telegram_connections SET is_verified=:verified, verified_at=IF(:verified_for_time=1,NOW(),verified_at), last_test_at=NOW(), last_test_status=:status WHERE user_id=:user_id');
        $verified = $success ? 1 : 0;
        $statement->execute(['verified' => $verified, 'verified_for_time' => $verified, 'status' => $success ? 'success' : 'failed', 'user_id' => $userId]);
    }
}
