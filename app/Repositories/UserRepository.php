<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;
use Throwable;

final class UserRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function findActiveByEmail(string $email): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM users WHERE email = :email AND is_active = 1 LIMIT 1');
        $statement->execute(['email' => strtolower(trim($email))]);
        return $statement->fetch() ?: null;
    }

    public function emailExists(string $email): bool
    {
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
        $statement->execute(['email' => strtolower(trim($email))]);
        return (int) $statement->fetchColumn() > 0;
    }

    public function createAccount(array $data): array
    {
        $this->pdo->beginTransaction();
        try {
            $user = $this->pdo->prepare('INSERT INTO users (name, email, password_hash, role, is_active) VALUES (:name, :email, :password_hash, "trader", 1)');
            $user->execute([
                'name' => $data['name'],
                'email' => $data['email'],
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            ]);
            $userId = (int) $this->pdo->lastInsertId();

            $settings = $this->pdo->prepare(
                'INSERT INTO user_settings (user_id, base_currency, timezone, starting_cash, current_cash, allow_fractional_shares, morning_report_enabled, morning_report_time, market_close_report_enabled, default_horizon)
                 VALUES (:user_id, :currency, :timezone, :starting_cash, :current_cash, :fractional, :morning, :morning_time, :close_report, :horizon)'
            );
            $settings->execute([
                'user_id' => $userId,
                'currency' => $data['base_currency'],
                'timezone' => $data['timezone'],
                'starting_cash' => $data['starting_cash'],
                'current_cash' => $data['starting_cash'],
                'fractional' => $data['allow_fractional_shares'] ? 1 : 0,
                'morning' => $data['morning_report_enabled'] ? 1 : 0,
                'morning_time' => $data['morning_report_time'],
                'close_report' => $data['market_close_report_enabled'] ? 1 : 0,
                'horizon' => $data['default_horizon'],
            ]);

            $portfolio = $this->pdo->prepare('INSERT INTO portfolios (user_id, name, base_currency) VALUES (:user_id, :name, :currency)');
            $portfolio->execute(['user_id' => $userId, 'name' => $data['portfolio_name'], 'currency' => $data['base_currency']]);
            $dashboard = $this->pdo->prepare('INSERT INTO dashboard_preferences (user_id, important_stock_ids) VALUES (:user_id, :important)');
            $dashboard->execute(['user_id' => $userId, 'important' => '[]']);

            $this->pdo->commit();
            return $this->findActiveByEmail($data['email']) ?? throw new \RuntimeException('The account was created but could not be loaded.');
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT id, name, email, role, is_active, last_login_at FROM users WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function markLogin(int $id): void
    {
        $statement = $this->pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $statement->execute(['id' => $id]);
    }

    public function verifyPassword(int $id, string $password): bool
    {
        $statement = $this->pdo->prepare('SELECT password_hash FROM users WHERE id = :id AND is_active = 1');
        $statement->execute(['id' => $id]);
        $hash = $statement->fetchColumn();
        return is_string($hash) && password_verify($password, $hash);
    }
}
