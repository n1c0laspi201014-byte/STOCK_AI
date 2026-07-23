<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\PortfolioRepository;
use App\Repositories\PriceSnapshotRepository;
use App\Repositories\StockRepository;
use App\Repositories\TransactionRepository;
use App\Support\Validator;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

final class PortfolioService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly PortfolioRepository $portfolios,
        private readonly TransactionRepository $transactions,
        private readonly StockRepository $stocks,
        private readonly PriceSnapshotRepository $snapshots,
        private readonly MarketDataService $market
    ) {}

    public function data(int $userId, bool $refreshMarketPrices = false): array
    {
        if ($refreshMarketPrices) {
            foreach ($this->portfolios->holdings($userId) as $holding) {
                try {
                    $this->market->quote((string) $holding['symbol'], (string) $holding['exchange_code']);
                } catch (Throwable) {
                    // The dashboard remains usable with the last honestly labeled quote.
                }
            }
        }
        $summary = $this->portfolios->summary($userId);
        $holdings = $this->portfolios->holdings($userId);
        $marketValue = 0.0;
        $unrealized = 0.0;
        foreach ($holdings as &$holding) {
            $holding['current_price'] = $holding['current_price'] !== null ? (float) $holding['current_price'] : null;
            $holding['market_value'] = $holding['market_value'] !== null ? (float) $holding['market_value'] : null;
            $holding['unrealized_profit_loss'] = $holding['unrealized_profit_loss'] !== null ? (float) $holding['unrealized_profit_loss'] : null;
            if ($holding['market_value'] !== null) $marketValue += $holding['market_value'];
            if ($holding['unrealized_profit_loss'] !== null) $unrealized += $holding['unrealized_profit_loss'];
            $holding['allocation_percent'] = 0.0;
        }
        unset($holding);
        foreach ($holdings as &$holding) {
            $holding['allocation_percent'] = $marketValue > 0 && $holding['market_value'] !== null ? ($holding['market_value'] / $marketValue) * 100 : 0.0;
        }
        unset($holding);
        $summary['market_value'] = $marketValue;
        $summary['portfolio_value'] = (float) ($summary['current_cash'] ?? 0) + $marketValue;
        $summary['unrealized_profit_loss'] = $unrealized;
        $summary['watchlist_count'] = $this->portfolios->watchlistCount($userId);
        $summary['data_timestamp'] = date(DATE_ATOM);
        return ['summary' => $summary, 'holdings' => $holdings, 'transactions' => $this->transactions->forUser($userId, 25)];
    }

    public function valueHistory(int $userId, int $days = 30): array
    {
        $days = max(1, min(366, $days));
        $start = (new \DateTimeImmutable("-{$days} days"))->format('Y-m-d H:i:s');
        $summary = $this->portfolios->summary($userId);
        $currency = (string) ($summary['base_currency'] ?? 'USD');
        $cash = (float) ($summary['starting_cash'] ?? 0);
        $quantities = [];
        $prices = [];
        $providers = [];
        $events = [];

        $transactions = $this->pdo->prepare(
            'SELECT t.stock_id,t.transaction_type,t.quantity,t.execution_price,t.net_cash_effect,t.executed_at,t.id
             FROM transactions t JOIN portfolios p ON p.id=t.portfolio_id
             WHERE p.user_id=:user_id ORDER BY t.executed_at ASC,t.id ASC LIMIT 5000'
        );
        $transactions->execute(['user_id' => $userId]);
        foreach ($transactions->fetchAll() as $transaction) {
            $stockId = (int) $transaction['stock_id'];
            $event = [
                'type' => 'transaction',
                'timestamp' => (string) $transaction['executed_at'],
                'stock_id' => $stockId,
                'side' => (string) $transaction['transaction_type'],
                'quantity' => (float) $transaction['quantity'],
                'price' => (float) $transaction['execution_price'],
                'cash_effect' => (float) $transaction['net_cash_effect'],
            ];
            if ($event['timestamp'] < $start) {
                $this->applyPortfolioHistoryEvent($event, $cash, $quantities, $prices, $providers);
            } else {
                $events[] = $event;
            }
        }

        $snapshots = $this->pdo->prepare(
            'SELECT ps.stock_id,ps.price,ps.provider,ps.received_at,ps.id
             FROM price_snapshots ps
             WHERE ps.received_at>=:start AND ps.stock_id IN (
                 SELECT DISTINCT t.stock_id FROM transactions t JOIN portfolios p ON p.id=t.portfolio_id WHERE p.user_id=:user_id
             )
             ORDER BY ps.received_at ASC,ps.id ASC LIMIT 10000'
        );
        $snapshots->execute(['start' => $start, 'user_id' => $userId]);
        foreach ($snapshots->fetchAll() as $snapshot) {
            $events[] = [
                'type' => 'quote',
                'timestamp' => (string) $snapshot['received_at'],
                'stock_id' => (int) $snapshot['stock_id'],
                'price' => (float) $snapshot['price'],
                'provider' => (string) $snapshot['provider'],
            ];
        }

        usort($events, static function(array $left, array $right): int {
            $time = strcmp((string) $left['timestamp'], (string) $right['timestamp']);
            if ($time !== 0) return $time;
            return ($left['type'] === 'quote' ? 0 : 1) <=> ($right['type'] === 'quote' ? 0 : 1);
        });

        $pointsByTimestamp = [];
        $baselineValue = $this->portfolioValue($cash, $quantities, $prices);
        $pointsByTimestamp[$start] = ['timestamp' => date(DATE_ATOM, strtotime($start)), 'close' => round($baselineValue, 2)];
        foreach ($events as $event) {
            $this->applyPortfolioHistoryEvent($event, $cash, $quantities, $prices, $providers);
            $timestamp = (string) $event['timestamp'];
            $pointsByTimestamp[$timestamp] = [
                'timestamp' => date(DATE_ATOM, strtotime($timestamp)),
                'close' => round($this->portfolioValue($cash, $quantities, $prices), 2),
            ];
        }

        $pointsByTimestamp[date('Y-m-d H:i:s')] = [
            'timestamp' => date(DATE_ATOM),
            'close' => round($this->portfolioValue($cash, $quantities, $prices), 2),
        ];
        $points = $this->downsampleHistory(array_values($pointsByTimestamp), 300);

        return [
            'points' => $points,
            'source' => 'Recorded market quotes and paper transactions',
            'providers' => array_values(array_unique(array_filter($providers))),
            'currency' => $currency,
            'is_actual_market_data' => true,
            'range_days' => $days,
        ];
    }

    public function buy(int $userId, string $symbol, float $quantity, string $exchange = '', bool $keepWatchlisted = false): array
    {
        return $this->executeTrade($userId, $symbol, $quantity, 'buy', $exchange, $keepWatchlisted);
    }

    public function sell(int $userId, string $symbol, float $quantity, string $exchange = ''): array
    {
        return $this->executeTrade($userId, $symbol, $quantity, 'sell', $exchange, false);
    }

    public function reset(int $userId): void
    {
        $this->pdo->beginTransaction();
        try {
            $portfolio = $this->lockPortfolio($userId);
            $this->pdo->prepare('DELETE FROM alert_events WHERE user_id=:user_id')->execute(['user_id' => $userId]);
            $this->pdo->prepare('DELETE FROM alert_rules WHERE user_id=:user_id')->execute(['user_id' => $userId]);
            $this->pdo->prepare('DELETE FROM predictions WHERE user_id=:user_id')->execute(['user_id' => $userId]);
            $this->pdo->prepare('DELETE FROM holdings WHERE portfolio_id=:portfolio_id')->execute(['portfolio_id' => $portfolio['id']]);
            $this->pdo->prepare('DELETE FROM transactions WHERE portfolio_id=:portfolio_id')->execute(['portfolio_id' => $portfolio['id']]);
            $this->pdo->prepare('UPDATE user_settings SET current_cash=starting_cash WHERE user_id=:user_id')->execute(['user_id' => $userId]);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
    }

    private function executeTrade(int $userId, string $symbol, float $quantity, string $type, string $exchange, bool $keepWatchlisted): array
    {
        $symbol = Validator::symbol($symbol);
        $quantity = Validator::positiveDecimal($quantity);
        $profile = $this->market->profile($symbol);
        $stock = $profile['success'] ? $this->stocks->upsert($profile['data']) : ($this->stocks->findBySymbol($symbol, $exchange) ?? []);
        if ($stock === [] || empty($stock['is_active'])) throw new InvalidArgumentException('Stock is unavailable or inactive.');
        $quoteResult = $this->market->quote($symbol, $exchange, true);
        if (!$quoteResult['success']) throw new InvalidArgumentException($quoteResult['message'] ?? 'Current quote unavailable. Trading is disabled.');
        $quote = $quoteResult['data'];
        $priceTime = strtotime((string) ($quote['provider_timestamp'] ?? $quote['received_at'] ?? ''));
        if (!$priceTime || time() - $priceTime > (int) config('market.stale_after_seconds', 900)) throw new InvalidArgumentException('The available quote is too stale for a simulated trade.');
        $price = (float) $quote['price'];
        if ($price <= 0) throw new InvalidArgumentException('Current quote unavailable.');

        $this->pdo->beginTransaction();
        try {
            $portfolio = $this->lockPortfolio($userId);
            $settings = $this->lockSettings($userId);
            if (empty($settings['allow_fractional_shares']) && floor($quantity) !== $quantity) throw new InvalidArgumentException('Fractional shares are disabled for this account.');
            $fee = (float) $settings['default_fee'];
            $gross = round($quantity * $price, 2);
            $holding = $this->lockHolding((int) $portfolio['id'], (int) $stock['id']);

            if ($type === 'buy') {
                $cashRequired = $gross + $fee;
                if ((float) $settings['current_cash'] < $cashRequired) throw new InvalidArgumentException('Insufficient virtual cash.');
                $oldQuantity = (float) ($holding['quantity'] ?? 0);
                $oldCost = (float) ($holding['total_cost'] ?? 0);
                $newQuantity = $oldQuantity + $quantity;
                $newCost = $oldCost + $gross + $fee;
                $average = $newQuantity > 0 ? $newCost / $newQuantity : 0;
                $this->upsertHolding((int) $portfolio['id'], (int) $stock['id'], $newQuantity, $average, $newCost, (float) ($holding['realized_profit_loss'] ?? 0));
                $cashEffect = -$cashRequired;
            } else {
                if ($holding === null || (float) $holding['quantity'] < $quantity) throw new InvalidArgumentException('You cannot sell more shares than you own.');
                $oldQuantity = (float) $holding['quantity'];
                $average = (float) $holding['average_cost'];
                $newQuantity = $oldQuantity - $quantity;
                $realized = (float) $holding['realized_profit_loss'] + (($price - $average) * $quantity) - $fee;
                $newCost = max(0, (float) $holding['total_cost'] - ($average * $quantity));
                $this->upsertHolding((int) $portfolio['id'], (int) $stock['id'], $newQuantity, $newQuantity > 0 ? $average : 0, $newCost, $realized);
                $cashEffect = $gross - $fee;
            }

            $transaction = $this->pdo->prepare('INSERT INTO transactions (portfolio_id,stock_id,transaction_type,quantity,execution_price,fee,gross_amount,net_cash_effect,executed_at,quote_timestamp,note) VALUES (:portfolio_id,:stock_id,:type,:quantity,:price,:fee,:gross,:cash_effect,NOW(),:quote_time,:note)');
            $transaction->execute(['portfolio_id' => $portfolio['id'], 'stock_id' => $stock['id'], 'type' => $type, 'quantity' => $quantity, 'price' => $price, 'fee' => $fee, 'gross' => $gross, 'cash_effect' => $cashEffect, 'quote_time' => date('Y-m-d H:i:s', $priceTime), 'note' => !empty($quote['is_delayed']) ? 'Executed using a delayed quote.' : 'Paper trade; no real order placed.']);
            $transactionId = (int) $this->pdo->lastInsertId();
            $cash = $this->pdo->prepare('UPDATE user_settings SET current_cash=current_cash+:effect WHERE user_id=:user_id');
            $cash->execute(['effect' => $cashEffect, 'user_id' => $userId]);
            if ($keepWatchlisted) $this->pdo->prepare('INSERT IGNORE INTO watchlist_items (user_id,stock_id) VALUES (:user_id,:stock_id)')->execute(['user_id' => $userId, 'stock_id' => $stock['id']]);
            $this->snapshots->save((int) $stock['id'], $quote);
            $this->pdo->commit();
            return ['transaction_id' => $transactionId, 'type' => $type, 'symbol' => $symbol, 'quantity' => $quantity, 'execution_price' => $price, 'fee' => $fee, 'gross_amount' => $gross, 'net_cash_effect' => $cashEffect, 'quote' => $quote, 'portfolio' => $this->data($userId)];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
    }

    private function lockPortfolio(int $userId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM portfolios WHERE user_id=:user_id FOR UPDATE');
        $statement->execute(['user_id' => $userId]);
        return $statement->fetch() ?: throw new RuntimeException('Portfolio not found. Run the seed utility.');
    }

    private function lockSettings(int $userId): array
    {
        $statement = $this->pdo->prepare('SELECT * FROM user_settings WHERE user_id=:user_id FOR UPDATE');
        $statement->execute(['user_id' => $userId]);
        return $statement->fetch() ?: throw new RuntimeException('Paper account settings not found.');
    }

    private function lockHolding(int $portfolioId, int $stockId): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM holdings WHERE portfolio_id=:portfolio_id AND stock_id=:stock_id FOR UPDATE');
        $statement->execute(['portfolio_id' => $portfolioId, 'stock_id' => $stockId]);
        return $statement->fetch() ?: null;
    }

    private function upsertHolding(int $portfolioId, int $stockId, float $quantity, float $average, float $cost, float $realized): void
    {
        $statement = $this->pdo->prepare('INSERT INTO holdings (portfolio_id,stock_id,quantity,average_cost,total_cost,realized_profit_loss,first_bought_at,last_transaction_at) VALUES (:portfolio_id,:stock_id,:quantity,:average,:cost,:realized,NOW(),NOW()) ON DUPLICATE KEY UPDATE quantity=VALUES(quantity),average_cost=VALUES(average_cost),total_cost=VALUES(total_cost),realized_profit_loss=VALUES(realized_profit_loss),last_transaction_at=NOW()');
        $statement->execute(['portfolio_id' => $portfolioId, 'stock_id' => $stockId, 'quantity' => $quantity, 'average' => $average, 'cost' => $cost, 'realized' => $realized]);
    }

    private function applyPortfolioHistoryEvent(array $event, float &$cash, array &$quantities, array &$prices, array &$providers): void
    {
        $stockId = (int) $event['stock_id'];
        if ($event['type'] === 'transaction') {
            $cash += (float) $event['cash_effect'];
            $direction = $event['side'] === 'buy' ? 1 : -1;
            $quantities[$stockId] = max(0, (float) ($quantities[$stockId] ?? 0) + ($direction * (float) $event['quantity']));
            $prices[$stockId] = (float) $event['price'];
            return;
        }
        $prices[$stockId] = (float) $event['price'];
        if (!empty($event['provider'])) $providers[] = (string) $event['provider'];
    }

    private function portfolioValue(float $cash, array $quantities, array $prices): float
    {
        $value = $cash;
        foreach ($quantities as $stockId => $quantity) {
            if ($quantity <= 0 || !isset($prices[$stockId])) continue;
            $value += $quantity * (float) $prices[$stockId];
        }
        return $value;
    }

    private function downsampleHistory(array $points, int $maximum): array
    {
        $count = count($points);
        if ($count <= $maximum) return $points;
        $selected = [];
        $lastIndex = -1;
        for ($slot = 0; $slot < $maximum; $slot++) {
            $index = (int) round($slot * ($count - 1) / ($maximum - 1));
            if ($index === $lastIndex) continue;
            $selected[] = $points[$index];
            $lastIndex = $index;
        }
        return $selected;
    }
}
