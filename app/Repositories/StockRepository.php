<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class StockRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function upsert(array $stock): array
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO stocks (symbol, exchange_code, company_name, currency, country, industry, logo_url, provider_symbol, is_active)
             VALUES (:symbol, :exchange_code, :company_name, :currency, :country, :industry, :logo_url, :provider_symbol, 1)
             ON DUPLICATE KEY UPDATE company_name = VALUES(company_name), currency = VALUES(currency), country = COALESCE(VALUES(country), country), industry = COALESCE(VALUES(industry), industry), logo_url = COALESCE(VALUES(logo_url), logo_url), provider_symbol = COALESCE(VALUES(provider_symbol), provider_symbol), is_active = 1'
        );
        $statement->execute([
            'symbol' => strtoupper((string) $stock['symbol']),
            'exchange_code' => (string) ($stock['exchange'] ?? $stock['exchange_code'] ?? ''),
            'company_name' => (string) ($stock['company_name'] ?? $stock['name'] ?? $stock['symbol']),
            'currency' => strtoupper((string) ($stock['currency'] ?? 'USD')),
            'country' => $stock['country'] ?? null,
            'industry' => $stock['industry'] ?? null,
            'logo_url' => $stock['logo_url'] ?? $stock['logo'] ?? null,
            'provider_symbol' => $stock['provider_symbol'] ?? $stock['symbol'],
        ]);
        return $this->findBySymbol((string) $stock['symbol'], (string) ($stock['exchange'] ?? $stock['exchange_code'] ?? '')) ?? [];
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM stocks WHERE id = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        return $statement->fetch() ?: null;
    }

    public function findBySymbol(string $symbol, string $exchange = ''): ?array
    {
        $sql = 'SELECT * FROM stocks WHERE symbol = :symbol';
        $params = ['symbol' => strtoupper($symbol)];
        if ($exchange !== '') {
            $sql .= ' AND exchange_code = :exchange';
            $params['exchange'] = $exchange;
        }
        $sql .= ' ORDER BY exchange_code = "" ASC LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetch() ?: null;
    }

    public function searchLocal(string $query, int $limit = 10): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM stocks WHERE is_active = 1 AND (symbol LIKE :symbol_query OR company_name LIKE :company_query) ORDER BY symbol LIMIT ' . max(1, min(25, $limit)));
        $search = '%' . $query . '%';
        $statement->execute(['symbol_query' => $search, 'company_query' => $search]);
        return $statement->fetchAll();
    }

    public function demoUniverse(): array
    {
        $symbols = config('market.demo_universe', []);
        if ($symbols === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($symbols), '?'));
        $statement = $this->pdo->prepare("SELECT * FROM stocks WHERE symbol IN ({$placeholders}) ORDER BY FIELD(symbol, {$placeholders})");
        $statement->execute([...$symbols, ...$symbols]);
        return $statement->fetchAll();
    }
}
