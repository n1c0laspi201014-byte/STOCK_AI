<?php
declare(strict_types=1);

namespace App\Integrations\MarketData;

final class TwelveDataMarketDataProvider implements MarketDataProviderInterface
{
    private const BASE_URL = 'https://api.twelvedata.com';

    public function __construct(private readonly string $apiKey) {}
    public function name(): string { return 'twelvedata'; }
    public function isConfigured(): bool { return trim($this->apiKey) !== ''; }

    public function search(string $query): array
    {
        $response = $this->get('/symbol_search', ['symbol' => $query, 'outputsize' => 15]);
        if (!$response['success']) return $response;
        $items = [];
        foreach ($response['data']['data'] ?? [] as $item) {
            if (!in_array(strtolower((string) ($item['instrument_type'] ?? '')), ['common stock', 'equity'], true)) continue;
            $items[] = ['symbol' => strtoupper((string) $item['symbol']), 'provider_symbol' => (string) $item['symbol'], 'company_name' => (string) ($item['instrument_name'] ?? $item['symbol']), 'exchange' => (string) ($item['exchange'] ?? ''), 'currency' => (string) ($item['currency'] ?? 'USD'), 'country' => (string) ($item['country'] ?? ''), 'provider' => $this->name()];
        }
        return ['success' => true, 'data' => $items];
    }

    public function quote(string $symbol, string $exchange = ''): array
    {
        $response = $this->get('/quote', ['symbol' => $symbol]);
        if (!$response['success']) return $response;
        $raw = $response['data'];
        $price = (float) ($raw['close'] ?? 0);
        if ($price <= 0) return $this->error('MARKET_QUOTE_UNAVAILABLE', 'Current quote unavailable.', false);
        return ['success' => true, 'data' => ['symbol' => strtoupper($symbol), 'exchange' => (string) ($raw['exchange'] ?? $exchange), 'currency' => (string) ($raw['currency'] ?? 'USD'), 'price' => $price, 'open' => (float) ($raw['open'] ?? 0), 'high' => (float) ($raw['high'] ?? 0), 'low' => (float) ($raw['low'] ?? 0), 'previous_close' => (float) ($raw['previous_close'] ?? 0), 'change_amount' => (float) ($raw['change'] ?? 0), 'change_percent' => (float) ($raw['percent_change'] ?? 0), 'volume' => isset($raw['volume']) ? (float) $raw['volume'] : null, 'market_status' => !empty($raw['is_market_open']) ? 'open' : 'closed', 'provider' => $this->name(), 'provider_timestamp' => isset($raw['timestamp']) ? gmdate(DATE_ATOM, (int) $raw['timestamp']) : gmdate(DATE_ATOM), 'received_at' => gmdate(DATE_ATOM), 'is_delayed' => true, 'delay_minutes' => null]];
    }

    public function profile(string $symbol): array
    {
        $response = $this->get('/profile', ['symbol' => $symbol]);
        if (!$response['success']) return $response;
        $raw = $response['data'];
        return ['success' => true, 'data' => ['symbol' => strtoupper($symbol), 'company_name' => $raw['name'] ?? $symbol, 'exchange' => $raw['exchange'] ?? '', 'country' => $raw['country'] ?? null, 'currency' => $raw['currency'] ?? 'USD', 'industry' => $raw['industry'] ?? null, 'logo_url' => $raw['logo'] ?? null, 'website' => $raw['website'] ?? null]];
    }

    public function history(string $symbol, string $range): array
    {
        $outputSize = ['1d' => 78, '7d' => 7, '1m' => 31, '3m' => 93, '1y' => 366][$range] ?? 31;
        $interval = $range === '1d' ? '5min' : '1day';
        $response = $this->get('/time_series', ['symbol' => $symbol, 'interval' => $interval, 'outputsize' => $outputSize, 'order' => 'ASC']);
        if (!$response['success']) return $response;
        $points = array_map(static fn(array $row): array => ['timestamp' => (string) $row['datetime'], 'open' => (float) $row['open'], 'high' => (float) $row['high'], 'low' => (float) $row['low'], 'close' => (float) $row['close'], 'volume' => isset($row['volume']) ? (float) $row['volume'] : null], $response['data']['values'] ?? []);
        return $points === [] ? $this->error('MARKET_HISTORY_UNAVAILABLE', 'Historical data is unavailable on the configured provider plan.', false) : ['success' => true, 'data' => ['points' => $points, 'source' => $this->name(), 'is_local_history' => false]];
    }

    public function news(string $symbol, int $limit = 5): array { return $this->error('MARKET_NEWS_UNAVAILABLE', 'News is unavailable from the fallback provider.', false); }
    public function marketStatus(string $exchange = 'US'): array { return ['success' => true, 'data' => ['status' => 'unknown', 'exchange' => $exchange, 'provider' => $this->name()]]; }

    private function get(string $path, array $query): array
    {
        if (!$this->isConfigured()) return $this->error('MARKET_NOT_CONFIGURED', 'Fallback market API key is not configured.', false);
        $url = self::BASE_URL . $path . '?' . http_build_query($query + ['apikey' => $this->apiKey]);
        $curl = curl_init($url);
        curl_setopt_array($curl, secure_curl_options([CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_HTTPHEADER => ['Accept: application/json']]));
        $body = curl_exec($curl); $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE); $error = curl_error($curl); curl_close($curl);
        if ($body === false || $error !== '') return $this->error('MARKET_NETWORK_ERROR', 'Could not reach Twelve Data.', true);
        $data = json_decode((string) $body, true);
        if ($status === 429 || ($data['code'] ?? null) === 429) return $this->error('MARKET_RATE_LIMIT', 'Market data request limit reached.', true);
        if (in_array($status, [401,403], true)) return $this->error('MARKET_AUTH_FAILED', 'Twelve Data rejected the API key or plan.', false);
        if (!is_array($data)) return $this->error('MARKET_INVALID_RESPONSE', 'Twelve Data returned invalid JSON.', true);
        if (($data['status'] ?? '') === 'error') return $this->error('MARKET_PROVIDER_ERROR', (string) ($data['message'] ?? 'Twelve Data request failed.'), false);
        return ['success' => true, 'data' => $data];
    }

    private function error(string $code, string $message, bool $retryable): array { return ['success' => false, 'error_code' => $code, 'message' => $message, 'retryable' => $retryable, 'cached_data_available' => false]; }
}
