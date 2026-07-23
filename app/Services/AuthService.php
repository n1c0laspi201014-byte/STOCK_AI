<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use InvalidArgumentException;
use PDOException;

final class AuthService
{
    public function __construct(private readonly UserRepository $users) {}

    public function attempt(string $email, string $password): bool
    {
        $attempts = $_SESSION['login_attempts'] ?? ['count' => 0, 'started' => time()];
        if (time() - (int) $attempts['started'] > 900) {
            $attempts = ['count' => 0, 'started' => time()];
        }
        if ((int) $attempts['count'] >= 8) {
            return false;
        }

        $user = $this->users->findActiveByEmail($email);
        if ($user === null || !password_verify($password, (string) $user['password_hash'])) {
            $attempts['count']++;
            $_SESSION['login_attempts'] = $attempts;
            usleep(200000);
            return false;
        }

        $this->startSession($user);
        $this->users->markLogin((int) $user['id']);
        return true;
    }

    public function register(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $password = (string) ($input['password'] ?? '');
        $confirmation = (string) ($input['password_confirmation'] ?? '');
        $timezone = trim((string) ($input['timezone'] ?? config('app.timezone', 'UTC')));
        $currency = strtoupper(trim((string) ($input['base_currency'] ?? 'USD')));
        $horizon = (string) ($input['default_horizon'] ?? '7d');
        $startingCash = is_numeric($input['starting_cash'] ?? null) ? (float) $input['starting_cash'] : 0;
        $morningTime = (string) ($input['morning_report_time'] ?? '07:30');
        $portfolioName = trim((string) ($input['portfolio_name'] ?? ''));

        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) throw new InvalidArgumentException('Your name must be between 2 and 100 characters.');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) throw new InvalidArgumentException('Enter a valid email address.');
        if ($this->users->emailExists($email)) throw new InvalidArgumentException('An account already exists for that email address.');
        if (strlen($password) < 10 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) throw new InvalidArgumentException('Use at least 10 characters with a letter and a number.');
        if (!hash_equals($password, $confirmation)) throw new InvalidArgumentException('The two passwords do not match.');
        if (!in_array($timezone, timezone_identifiers_list(), true)) throw new InvalidArgumentException('Choose a valid timezone.');
        if (!in_array($currency, ['USD', 'EUR'], true)) throw new InvalidArgumentException('Choose USD or EUR as the paper-account currency.');
        if (!in_array($horizon, ['1d', '7d', '30d'], true)) throw new InvalidArgumentException('Choose a valid prediction horizon.');
        if ($startingCash < 1000 || $startingCash > 1000000) throw new InvalidArgumentException('Starting virtual cash must be between 1,000 and 1,000,000.');
        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $morningTime)) throw new InvalidArgumentException('Choose a valid morning-report time.');
        if ($portfolioName === '') $portfolioName = $name . "'s Paper Portfolio";
        if (mb_strlen($portfolioName) > 120) throw new InvalidArgumentException('Portfolio name must be 120 characters or fewer.');

        try {
            $user = $this->users->createAccount([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'timezone' => $timezone,
                'base_currency' => $currency,
                'starting_cash' => round($startingCash, 2),
                'allow_fractional_shares' => !empty($input['allow_fractional_shares']),
                'morning_report_enabled' => !empty($input['morning_report_enabled']),
                'morning_report_time' => $morningTime . ':00',
                'market_close_report_enabled' => !empty($input['market_close_report_enabled']),
                'default_horizon' => $horizon,
                'portfolio_name' => $portfolioName,
            ]);
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') throw new InvalidArgumentException('An account already exists for that email address.');
            throw $exception;
        }

        $this->startSession($user);
        $this->users->markLogin((int) $user['id']);
        return $user;
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    private function startSession(array $user): void
    {
        if (PHP_SAPI !== 'cli') session_regenerate_id(true);
        unset($_SESSION['login_attempts']);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => (string) $user['name'],
            'email' => (string) $user['email'],
            'role' => (string) $user['role'],
        ];
    }
}
