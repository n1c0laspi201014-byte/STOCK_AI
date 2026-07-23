<?php
declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class PredictionRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function save(int $userId, int $stockId, array $prediction): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO predictions (user_id, stock_id, horizon, `signal`, estimated_probability_up, estimated_probability_down, confidence_score, risk_level, technical_score, news_score, market_score, summary, positive_factors, negative_factors, invalidation_conditions, source_data_timestamp, model_name, status, generated_at, expires_at)
             VALUES (:user_id, :stock_id, :horizon, :signal, :probability_up, :probability_down, :confidence, :risk, :technical, :news, :market, :summary, :positive, :negative, :invalidations, :source_time, :model, :status, NOW(), :expires_at)'
        );
        $statement->execute([
            'user_id' => $userId,
            'stock_id' => $stockId,
            'horizon' => $prediction['horizon'],
            'signal' => $prediction['signal'],
            'probability_up' => $prediction['estimated_probability_up'],
            'probability_down' => $prediction['estimated_probability_down'],
            'confidence' => $prediction['confidence_score'],
            'risk' => $prediction['risk_level'],
            'technical' => $prediction['technical_score'],
            'news' => $prediction['news_score'],
            'market' => $prediction['market_score'],
            'summary' => $prediction['summary'],
            'positive' => json_encode($prediction['positive_factors'] ?? []),
            'negative' => json_encode($prediction['negative_factors'] ?? []),
            'invalidations' => json_encode($prediction['invalidation_conditions'] ?? []),
            'source_time' => $prediction['source_data_timestamp'] ?? null,
            'model' => $prediction['model_name'],
            'status' => $prediction['status'],
            'expires_at' => $prediction['expires_at'],
        ]);
        $id = (int) $this->pdo->lastInsertId();
        if (isset($prediction['start_price']) && (float) $prediction['start_price'] > 0) {
            $outcome = $this->pdo->prepare('INSERT INTO prediction_outcomes (prediction_id, start_price) VALUES (:prediction_id, :start_price)');
            $outcome->execute(['prediction_id' => $id, 'start_price' => $prediction['start_price']]);
        }
        return $id;
    }

    public function latest(int $userId, int $stockId, ?string $horizon = null): ?array
    {
        $sql = 'SELECT p.*, s.symbol, s.company_name, s.currency FROM predictions p JOIN stocks s ON s.id = p.stock_id WHERE p.user_id = :user_id AND p.stock_id = :stock_id';
        $params = ['user_id' => $userId, 'stock_id' => $stockId];
        if ($horizon !== null) {
            $sql .= ' AND p.horizon = :horizon';
            $params['horizon'] = $horizon;
        }
        $sql .= ' ORDER BY p.generated_at DESC, p.id DESC LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return $this->decode($statement->fetch() ?: null);
    }

    public function forOwned(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT p.*, s.symbol, s.company_name, s.currency FROM predictions p
             JOIN stocks s ON s.id = p.stock_id
             JOIN holdings h ON h.stock_id = s.id AND h.quantity > 0
             JOIN portfolios po ON po.id = h.portfolio_id AND po.user_id = p.user_id
             WHERE p.user_id = :user_id AND p.id = (SELECT p2.id FROM predictions p2 WHERE p2.user_id = p.user_id AND p2.stock_id = p.stock_id ORDER BY p2.generated_at DESC, p2.id DESC LIMIT 1)
             ORDER BY s.symbol'
        );
        $statement->execute(['user_id' => $userId]);
        return array_map([$this, 'decode'], $statement->fetchAll());
    }

    public function forWatchlist(int $userId): array
    {
        $statement = $this->pdo->prepare(
            'SELECT p.*, s.symbol, s.company_name, s.currency FROM predictions p
             JOIN stocks s ON s.id = p.stock_id
             JOIN watchlist_items w ON w.stock_id = s.id AND w.user_id = p.user_id
             WHERE p.user_id = :user_id AND p.id = (SELECT p2.id FROM predictions p2 WHERE p2.user_id = p.user_id AND p2.stock_id = p.stock_id ORDER BY p2.generated_at DESC, p2.id DESC LIMIT 1)
             ORDER BY s.symbol'
        );
        $statement->execute(['user_id' => $userId]);
        return array_map([$this, 'decode'], $statement->fetchAll());
    }

    public function history(int $userId, ?int $stockId = null, int $limit = 100): array
    {
        $sql = 'SELECT p.*, s.symbol, s.company_name, o.start_price, o.end_price, o.actual_change_percent, o.outcome FROM predictions p JOIN stocks s ON s.id = p.stock_id LEFT JOIN prediction_outcomes o ON o.prediction_id = p.id WHERE p.user_id = :user_id';
        $params = ['user_id' => $userId];
        if ($stockId !== null) {
            $sql .= ' AND p.stock_id = :stock_id';
            $params['stock_id'] = $stockId;
        }
        $sql .= ' ORDER BY p.generated_at DESC LIMIT ' . max(1, min(500, $limit));
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return array_map([$this, 'decode'], $statement->fetchAll());
    }

    public function evaluateDue(int $userId, callable $priceResolver): int
    {
        $statement = $this->pdo->prepare(
            'SELECT p.id, p.stock_id, p.`signal` AS `signal`, o.start_price FROM predictions p JOIN prediction_outcomes o ON o.prediction_id = p.id
             WHERE p.user_id = :user_id AND o.outcome = "pending" AND p.expires_at <= NOW()'
        );
        $statement->execute(['user_id' => $userId]);
        $updated = 0;
        foreach ($statement->fetchAll() as $row) {
            $price = $priceResolver((int) $row['stock_id']);
            if (!is_numeric($price) || (float) $price <= 0) {
                continue;
            }
            $change = (((float) $price - (float) $row['start_price']) / (float) $row['start_price']) * 100;
            $bullish = in_array($row['signal'], ['buy', 'watch', 'hold'], true);
            $outcome = abs($change) < 0.25 ? 'neutral' : (($bullish && $change > 0) || (!$bullish && $change < 0) ? 'correct' : 'incorrect');
            $update = $this->pdo->prepare('UPDATE prediction_outcomes SET end_price = :price, actual_change_percent = :change, outcome = :outcome, evaluated_at = NOW() WHERE prediction_id = :id');
            $update->execute(['price' => $price, 'change' => $change, 'outcome' => $outcome, 'id' => $row['id']]);
            $updated++;
        }
        return $updated;
    }

    private function decode(?array $row): ?array
    {
        if ($row === null) {
            return null;
        }
        foreach (['positive_factors', 'negative_factors', 'invalidation_conditions'] as $field) {
            $row[$field] = json_decode((string) ($row[$field] ?? '[]'), true) ?: [];
        }
        return $row;
    }
}
