<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Env;
use App\Integrations\MarketData\FinnhubMarketDataProvider;
use App\Integrations\MarketData\MarketDataProviderInterface;
use App\Integrations\MarketData\TwelveDataMarketDataProvider;
use App\Integrations\MarketData\YahooFinanceHistoryProvider;
use App\Repositories\PriceSnapshotRepository;
use App\Repositories\StockRepository;

final class MarketDataService
{
    private MarketDataProviderInterface $primary;
    private ?MarketDataProviderInterface $fallback;
    private YahooFinanceHistoryProvider $historyFallback;

    public function __construct(private readonly StockRepository $stocks, private readonly PriceSnapshotRepository $snapshots)
    {
        $this->primary = $this->provider((string) config('market.provider', 'finnhub'), (string) config('market.api_key', ''));
        $fallbackKey = (string) config('market.fallback_api_key', '');
        $this->fallback = $fallbackKey !== '' ? $this->provider((string) config('market.fallback_provider', 'twelvedata'), $fallbackKey) : null;
        $this->historyFallback = new YahooFinanceHistoryProvider();
    }

    public function search(string $query): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 1) return ['success' => false, 'error_code' => 'SEARCH_REQUIRED', 'message' => 'Enter a symbol or company name.', 'retryable' => false];
        $result = $this->cached('search-' . strtolower($query), 86400, fn() => $this->primary->search($query));
        if (!$result['success'] && $this->fallback?->isConfigured()) $result = $this->fallback->search($query);
        if (!$result['success']) {
            $local = $this->stocks->searchLocal($query);
            return ['success' => true, 'data' => array_map(static fn(array $stock): array => ['id' => (int) $stock['id'], 'symbol' => $stock['symbol'], 'company_name' => $stock['company_name'], 'exchange' => $stock['exchange_code'], 'currency' => $stock['currency'], 'country' => $stock['country'], 'provider' => 'local-catalog', 'quote' => null], $local), 'warning' => $result['message'], 'source' => 'local-catalog'];
        }
        $data = [];
        $enrichProfiles = count($result['data']) <= 3;
        foreach ($result['data'] as $stockData) {
            if ($enrichProfiles) {
                $profile = $this->cached('profile-' . strtolower((string) $stockData['symbol']), 86400, fn() => $this->primary->profile((string) $stockData['symbol']));
                if (($profile['success'] ?? false) === true) {
                    foreach (['company_name', 'exchange', 'country', 'currency', 'industry', 'logo_url'] as $field) {
                        if (($profile['data'][$field] ?? null) !== null && $profile['data'][$field] !== '') {
                            $stockData[$field] = $profile['data'][$field];
                        }
                    }
                }
            }
            $stock = $this->stocks->upsert($stockData);
            $stockData['id'] = (int) $stock['id'];
            $stockData['quote'] = null;
            $data[] = $stockData;
        }
        return ['success' => true, 'data' => $data, 'source' => $this->primary->name()];
    }

    public function quote(string $symbol, string $exchange = '', bool $force = false): array
    {
        $stock = $this->stocks->findBySymbol($symbol, $exchange) ?? $this->stocks->upsert(['symbol' => $symbol, 'company_name' => $symbol, 'exchange' => $exchange]);
        $resolver = fn() => $this->primary->quote($symbol, $exchange);
        $result = $force ? $resolver() : $this->cached('quote-' . strtolower($symbol . '-' . $exchange), (int) config('market.cache_seconds', 60), $resolver);
        if (!$result['success'] && $this->fallback?->isConfigured()) $result = $this->fallback->quote($symbol, $exchange);
        if ($result['success']) {
            $result['data']['freshness'] = !empty($result['cached']) ? 'Cached' : $this->freshness($result['data']);
            $this->snapshots->save((int) $stock['id'], $result['data']);
            return $result;
        }
        $cached = $this->snapshots->latest((int) $stock['id']);
        if ($cached !== null) {
            $age = time() - strtotime((string) $cached['received_at']);
            $quote = ['symbol' => strtoupper($symbol), 'exchange' => $exchange, 'currency' => $stock['currency'] ?? 'USD', 'price' => (float) $cached['price'], 'open' => isset($cached['open_price']) ? (float) $cached['open_price'] : null, 'high' => isset($cached['high_price']) ? (float) $cached['high_price'] : null, 'low' => isset($cached['low_price']) ? (float) $cached['low_price'] : null, 'previous_close' => isset($cached['previous_close']) ? (float) $cached['previous_close'] : null, 'change_amount' => (float) $cached['price'] - (float) ($cached['previous_close'] ?: $cached['price']), 'change_percent' => (float) ($cached['previous_close'] ?: 0) > 0 ? (((float) $cached['price'] - (float) $cached['previous_close']) / (float) $cached['previous_close']) * 100 : 0, 'volume' => $cached['volume'] !== null ? (float) $cached['volume'] : null, 'market_status' => 'unknown', 'provider' => $cached['provider'], 'provider_timestamp' => $cached['provider_timestamp'], 'received_at' => $cached['received_at'], 'is_delayed' => true, 'delay_minutes' => (int) floor($age / 60), 'freshness' => $age > (int) config('market.stale_after_seconds', 900) ? 'Stale' : 'Cached'];
            return ['success' => true, 'data' => $quote, 'warning' => 'Showing the most recent cached price from ' . $cached['received_at'] . '.', 'cached' => true];
        }
        $result['cached_data_available'] = false;
        return $result;
    }

    public function profile(string $symbol): array
    {
        $result = $this->cached('profile-' . strtolower($symbol), 86400, fn() => $this->primary->profile($symbol));
        if ($result['success']) {
            $this->stocks->upsert($result['data']);
            return $result;
        }
        $stock = $this->stocks->findBySymbol($symbol);
        return $stock ? ['success' => true, 'data' => ['symbol' => $stock['symbol'], 'company_name' => $stock['company_name'], 'exchange' => $stock['exchange_code'], 'country' => $stock['country'], 'currency' => $stock['currency'], 'industry' => $stock['industry'], 'logo_url' => $stock['logo_url']], 'source' => 'local-catalog'] : $result;
    }

    public function history(string $symbol, string $range): array
    {
        $result = $this->cached('history-' . strtolower($symbol . '-' . $range), 900, fn() => $this->primary->history($symbol, $range));
        if (!$result['success'] && $this->fallback?->isConfigured()) $result = $this->fallback->history($symbol, $range);
        if ($result['success'] && !empty($result['data']['points'])) return $result;
        $externalHistory = $this->cached('history-yahoo-' . strtolower($symbol . '-' . $range), 900, fn() => $this->historyFallback->history($symbol, $range));
        if ($externalHistory['success'] && !empty($externalHistory['data']['points'])) return $externalHistory;
        $stock = $this->stocks->findBySymbol($symbol);
        $days = ['1d' => 1, '7d' => 7, '1m' => 31, '3m' => 93, '1y' => 366][$range] ?? 31;
        $points = $stock ? $this->snapshots->history((int) $stock['id'], $days) : [];
        return $points !== [] ? ['success' => true, 'data' => ['points' => $points, 'source' => 'local price snapshots', 'is_local_history' => true], 'warning' => 'Showing locally accumulated history.'] : ['success' => false, 'error_code' => 'MARKET_HISTORY_UNAVAILABLE', 'message' => 'Historical data is unavailable on the configured provider plan.', 'retryable' => false, 'cached_data_available' => false];
    }

    public function news(string $symbol): array
    {
        return $this->cached('news-' . strtolower($symbol), 900, fn() => $this->primary->news($symbol));
    }

    public function marketStatus(string $exchange = 'US'): array
    {
        return $this->cached('status-' . strtolower($exchange), 300, fn() => $this->primary->marketStatus($exchange));
    }

    public function providerConfigured(): bool { return $this->primary->isConfigured(); }

    private function provider(string $name, string $key): MarketDataProviderInterface
    {
        return strtolower($name) === 'twelvedata' ? new TwelveDataMarketDataProvider($key) : new FinnhubMarketDataProvider($key);
    }

    private function cached(string $key, int $ttl, callable $loader): array
    {
        $file = base_path('storage/cache/' . hash('sha256', $key) . '.json');
        if (is_file($file) && filemtime($file) >= time() - $ttl) {
            $cached = json_decode((string) file_get_contents($file), true);
            if (is_array($cached)) {
                $cached['cached'] = true;
                return $cached;
            }
        }
        $result = $loader();
        if (($result['success'] ?? false) === true) @file_put_contents($file, json_encode($result, JSON_UNESCAPED_SLASHES), LOCK_EX);
        return $result;
    }

    private function freshness(array $quote): string
    {
        if (!empty($quote['is_delayed'])) return isset($quote['delay_minutes']) ? 'Delayed by approximately ' . (int) $quote['delay_minutes'] . ' minutes' : 'Delayed';
        if (($quote['market_status'] ?? 'unknown') === 'closed') return 'Last close';
        $timestamp = strtotime((string) ($quote['provider_timestamp'] ?? $quote['received_at'] ?? ''));
        if ($timestamp && time() - $timestamp > (int) config('market.stale_after_seconds', 900)) return 'Stale';
        if (($quote['market_status'] ?? 'unknown') !== 'open') return 'Cached';
        return 'Live';
    }
}
