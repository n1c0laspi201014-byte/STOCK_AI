<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class PriceSnapshotRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function save(int $stockId, array $quote): void
    {
        if (!isset($quote['price']) || (float) $quote['price'] <= 0) {
            return;
        }
        $providerTimestamp = isset($quote['provider_timestamp']) ? date('Y-m-d H:i:s', strtotime((string) $quote['provider_timestamp'])) : null;
        $latest = $this->latest($stockId);
        if (
            $latest !== null
            && $providerTimestamp !== null
            && (string) ($latest['provider_timestamp'] ?? '') === $providerTimestamp
            && abs((float) $latest['price'] - (float) $quote['price']) < 0.00000001
        ) {
            return;
        }
        $statement = $this->pdo->prepare(
            'INSERT INTO price_snapshots (stock_id, price, open_price, high_price, low_price, previous_close, volume, provider, provider_timestamp, received_at, is_delayed, delay_minutes)
             VALUES (:stock_id, :price, :open_price, :high_price, :low_price, :previous_close, :volume, :provider, :provider_timestamp, NOW(), :is_delayed, :delay_minutes)'
        );
        $statement->execute([
            'stock_id' => $stockId,
            'price' => $quote['price'],
            'open_price' => $quote['open'] ?? null,
            'high_price' => $quote['high'] ?? null,
            'low_price' => $quote['low'] ?? null,
            'previous_close' => $quote['previous_close'] ?? null,
            'volume' => $quote['volume'] ?? null,
            'provider' => $quote['provider'] ?? 'unknown',
            'provider_timestamp' => $providerTimestamp,
            'is_delayed' => !empty($quote['is_delayed']) ? 1 : 0,
            'delay_minutes' => $quote['delay_minutes'] ?? null,
        ]);
    }

    public function latest(int $stockId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM price_snapshots WHERE stock_id = :stock_id ORDER BY received_at DESC LIMIT 1');
        $statement->execute(['stock_id' => $stockId]);
        return $statement->fetch() ?: null;
    }

    public function history(int $stockId, int $days = 30, int $limit = 500): array
    {
        $statement = $this->pdo->prepare('SELECT price AS close, volume, received_at AS timestamp FROM price_snapshots WHERE stock_id = :stock_id AND received_at >= DATE_SUB(NOW(), INTERVAL :days DAY) ORDER BY received_at ASC LIMIT ' . max(2, min(1000, $limit)));
        $statement->bindValue(':stock_id', $stockId, PDO::PARAM_INT);
        $statement->bindValue(':days', max(1, $days), PDO::PARAM_INT);
        $statement->execute();
        return $statement->fetchAll();
    }
}
