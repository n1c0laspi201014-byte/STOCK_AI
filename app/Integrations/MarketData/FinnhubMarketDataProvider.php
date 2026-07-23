<?php
declare(strict_types=1);

namespace App\Integrations\MarketData;

final class FinnhubMarketDataProvider implements MarketDataProviderInterface
{
    private const BASE_URL = 'https://finnhub.io/api/v1';

    public function __construct(private readonly string $apiKey) {}

    public function name(): string { return 'finnhub'; }
    public function isConfigured(): bool { return trim($this->apiKey) !== ''; }

    public function search(string $query): array
    {
        $response = $this->get('/search', ['q' => $query]);
        if (!$response['success']) return $response;
        $items = [];
        foreach (array_slice($response['data']['result'] ?? [], 0, 15) as $result) {
            if (($result['type'] ?? '') !== 'Common Stock') continue;
            $display = (string) ($result['displaySymbol'] ?? $result['symbol'] ?? '');
            $items[] = [
                'symbol' => strtoupper($display),
                'provider_symbol' => (string) ($result['symbol'] ?? $display),
                'company_name' => (string) ($result['description'] ?? $display),
                'exchange' => '',
                'currency' => 'USD',
                'country' => null,
                'provider' => $this->name(),
            ];
        }
        $normalizedQuery = strtoupper(trim($query));
        $exact = array_values(array_filter($items, static fn(array $item): bool => strtoupper((string) $item['symbol']) === $normalizedQuery));
        if ($exact !== []) {
            $items = $exact;
        } else {
            // Finnhub commonly returns foreign suffix variants for a company
            // search. Prefer the primary unsuffixed listings when available.
            $primaryListings = array_values(array_filter($items, static fn(array $item): bool => !str_contains((string) $item['symbol'], '.')));
            if ($primaryListings !== []) {
                $items = $primaryListings;
            }
        }
        return ['success' => true, 'data' => array_slice($items, 0, 8)];
    }

    public function quote(string $symbol, string $exchange = ''): array
    {
        $response = $this->get('/quote', ['symbol' => $symbol]);
        if (!$response['success']) return $response;
        $raw = $response['data'];
        if (empty($raw['c']) || (float) $raw['c'] <= 0) return $this->error('MARKET_QUOTE_UNAVAILABLE', 'Current quote unavailable.', false);
        $timestamp = !empty($raw['t']) ? gmdate(DATE_ATOM, (int) $raw['t']) : gmdate(DATE_ATOM);
        return ['success' => true, 'data' => [
            'symbol' => strtoupper($symbol), 'exchange' => $exchange, 'currency' => 'USD',
            'price' => (float) $raw['c'], 'open' => (float) ($raw['o'] ?? 0), 'high' => (float) ($raw['h'] ?? 0),
            'low' => (float) ($raw['l'] ?? 0), 'previous_close' => (float) ($raw['pc'] ?? 0),
            'change_amount' => (float) ($raw['d'] ?? ((float) $raw['c'] - (float) ($raw['pc'] ?? 0))),
            'change_percent' => (float) ($raw['dp'] ?? 0), 'volume' => null,
            'market_status' => $this->inferMarketStatus(), 'provider' => $this->name(),
            'provider_timestamp' => $timestamp, 'received_at' => gmdate(DATE_ATOM), 'is_delayed' => false, 'delay_minutes' => null,
        ]];
    }

    public function profile(string $symbol): array
    {
        $response = $this->get('/stock/profile2', ['symbol' => $symbol]);
        if (!$response['success']) return $response;
        $raw = $response['data'];
        return ['success' => true, 'data' => [
            'symbol' => strtoupper($symbol), 'company_name' => $raw['name'] ?? $symbol, 'exchange' => $this->normalizeExchange((string) ($raw['exchange'] ?? '')),
            'country' => $raw['country'] ?? null, 'currency' => $raw['currency'] ?? 'USD', 'industry' => $raw['finnhubIndustry'] ?? null,
            'logo_url' => $raw['logo'] ?? null, 'website' => $raw['weburl'] ?? null, 'market_cap' => $raw['marketCapitalization'] ?? null,
        ]];
    }

    public function history(string $symbol, string $range): array
    {
        $days = ['1d' => 1, '7d' => 7, '1m' => 31, '3m' => 93, '1y' => 366][$range] ?? 31;
        $resolution = $range === '1d' ? '15' : 'D';
        $response = $this->get('/stock/candle', ['symbol' => $symbol, 'resolution' => $resolution, 'from' => time() - ($days * 86400), 'to' => time()]);
        if (!$response['success']) return $response;
        $raw = $response['data'];
        if (($raw['s'] ?? '') !== 'ok') return $this->error('MARKET_HISTORY_UNAVAILABLE', 'Historical data is unavailable on the configured provider plan.', false);
        $points = [];
        foreach (($raw['t'] ?? []) as $i => $timestamp) {
            $points[] = ['timestamp' => gmdate(DATE_ATOM, (int) $timestamp), 'open' => (float) ($raw['o'][$i] ?? 0), 'high' => (float) ($raw['h'][$i] ?? 0), 'low' => (float) ($raw['l'][$i] ?? 0), 'close' => (float) ($raw['c'][$i] ?? 0), 'volume' => (float) ($raw['v'][$i] ?? 0)];
        }
        return ['success' => true, 'data' => ['points' => $points, 'source' => $this->name(), 'is_local_history' => false]];
    }

    public function news(string $symbol, int $limit = 5): array
    {
        $response = $this->get('/company-news', ['symbol' => $symbol, 'from' => date('Y-m-d', time() - 7 * 86400), 'to' => date('Y-m-d')]);
        if (!$response['success']) return $response;
        $items = array_map(static fn(array $item): array => ['headline' => $item['headline'] ?? '', 'summary' => $item['summary'] ?? '', 'source' => $item['source'] ?? '', 'url' => $item['url'] ?? '', 'datetime' => !empty($item['datetime']) ? gmdate(DATE_ATOM, (int) $item['datetime']) : null], array_slice($response['data'] ?? [], 0, $limit));
        return ['success' => true, 'data' => $items];
    }

    public function marketStatus(string $exchange = 'US'): array
    {
        $requestedExchange = $exchange === '' ? 'US' : strtoupper(trim($exchange));
        $usExchanges = ['US', 'NASDAQ', 'NYSE', 'NYSE ARCA', 'AMEX'];
        $providerExchange = in_array($requestedExchange, $usExchanges, true) ? 'US' : $requestedExchange;
        $response = $this->get('/stock/market-status', ['exchange' => $providerExchange]);
        if (!$response['success']) {
            if ($providerExchange !== 'US') return $response;
            return [
                'success' => true,
                'data' => ['status' => $this->inferMarketStatus(), 'exchange' => $requestedExchange, 'session' => null, 'provider' => $this->name(), 'source' => 'US weekday/session schedule'],
                'warning' => 'Finnhub market status was unavailable; using the configured US market schedule.',
            ];
        }
        return ['success' => true, 'data' => ['status' => !empty($response['data']['isOpen']) ? 'open' : 'closed', 'exchange' => $requestedExchange, 'session' => $response['data']['session'] ?? null, 'provider' => $this->name()]];
    }

    private function get(string $path, array $query): array
    {
        if (!$this->isConfigured()) return $this->error('MARKET_NOT_CONFIGURED', 'Market API key is not configured.', false);
        $url = self::BASE_URL . $path . '?' . http_build_query($query);
        $curl = curl_init($url);
        curl_setopt_array($curl, secure_curl_options([CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_HTTPHEADER => ['Accept: application/json', 'X-Finnhub-Token: ' . $this->apiKey]]));
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        if ($body === false || $error !== '') return $this->error('MARKET_NETWORK_ERROR', 'Could not reach Finnhub.', true);
        if ($status === 429) return $this->error('MARKET_RATE_LIMIT', 'Market data request limit reached.', true);
        if (in_array($status, [401, 403], true)) return $this->error('MARKET_AUTH_FAILED', 'Finnhub rejected the API key or plan.', false);
        if ($status >= 400) return $this->error('MARKET_PROVIDER_ERROR', "Finnhub request failed with HTTP {$status}.", $status >= 500);
        $data = json_decode((string) $body, true);
        if (!is_array($data)) return $this->error('MARKET_INVALID_RESPONSE', 'Finnhub returned invalid JSON.', true);
        if (isset($data['error'])) return $this->error('MARKET_PROVIDER_ERROR', (string) $data['error'], false);
        return ['success' => true, 'data' => $data];
    }

    private function error(string $code, string $message, bool $retryable): array
    {
        return ['success' => false, 'error_code' => $code, 'message' => $message, 'retryable' => $retryable, 'cached_data_available' => false];
    }

    private function inferMarketStatus(): string
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('America/New_York'));
        $minutes = ((int) $now->format('H')) * 60 + (int) $now->format('i');
        return (int) $now->format('N') <= 5 && $minutes >= 570 && $minutes < 960 ? 'open' : 'closed';
    }

    private function normalizeExchange(string $exchange): string
    {
        $upper = strtoupper(trim($exchange));
        if (str_contains($upper, 'NASDAQ')) return 'NASDAQ';
        if (str_contains($upper, 'NEW YORK STOCK EXCHANGE') || $upper === 'NYSE') return 'NYSE';
        if (str_contains($upper, 'NYSE ARCA')) return 'NYSE ARCA';
        if (str_contains($upper, 'AMERICAN STOCK EXCHANGE') || $upper === 'AMEX') return 'AMEX';
        return trim($exchange);
    }
}
