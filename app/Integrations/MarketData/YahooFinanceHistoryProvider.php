<?php
declare(strict_types=1);

namespace App\Integrations\MarketData;

final class YahooFinanceHistoryProvider
{
    private const BASE_URL = 'https://query1.finance.yahoo.com/v8/finance/chart/';

    public function history(string $symbol, string $range): array
    {
        [$providerRange, $interval] = match ($range) {
            '1d' => ['1d', '5m'],
            '7d' => ['5d', '15m'],
            '3m' => ['3mo', '1d'],
            '1y' => ['1y', '1d'],
            default => ['1mo', '1d'],
        };

        $providerSymbol = $this->providerSymbol($symbol);
        $url = self::BASE_URL . rawurlencode($providerSymbol) . '?' . http_build_query([
            'range' => $providerRange,
            'interval' => $interval,
            'includePrePost' => 'false',
            'events' => 'history',
        ]);
        $curl = curl_init($url);
        curl_setopt_array($curl, secure_curl_options([
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: Mozilla/5.0 PaperTradeAI/1.0'],
        ]));
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($body === false || $error !== '') return $this->error('MARKET_NETWORK_ERROR', 'Could not reach the historical chart provider.', true);
        if ($status === 429) return $this->error('MARKET_RATE_LIMIT', 'Historical chart request limit reached.', true);
        if ($status >= 400) return $this->error('MARKET_HISTORY_UNAVAILABLE', 'Historical chart data is unavailable for this symbol.', $status >= 500);

        $decoded = json_decode((string) $body, true);
        if (!is_array($decoded)) return $this->error('MARKET_INVALID_RESPONSE', 'Historical chart provider returned invalid JSON.', true);
        $chart = $decoded['chart'] ?? [];
        if (!empty($chart['error'])) return $this->error('MARKET_HISTORY_UNAVAILABLE', (string) ($chart['error']['description'] ?? 'Historical chart data is unavailable.'), false);
        $result = $chart['result'][0] ?? null;
        if (!is_array($result)) return $this->error('MARKET_HISTORY_UNAVAILABLE', 'Historical chart data is unavailable for this symbol.', false);

        $timestamps = $result['timestamp'] ?? [];
        $quote = $result['indicators']['quote'][0] ?? [];
        $points = [];
        foreach ($timestamps as $index => $timestamp) {
            $close = $quote['close'][$index] ?? null;
            if (!is_numeric($close) || (float) $close <= 0) continue;
            $points[] = [
                'timestamp' => gmdate(DATE_ATOM, (int) $timestamp),
                'open' => $this->numberOrNull($quote['open'][$index] ?? null),
                'high' => $this->numberOrNull($quote['high'][$index] ?? null),
                'low' => $this->numberOrNull($quote['low'][$index] ?? null),
                'close' => (float) $close,
                'volume' => $this->numberOrNull($quote['volume'][$index] ?? null),
            ];
        }
        if ($points === []) return $this->error('MARKET_HISTORY_UNAVAILABLE', 'Historical chart data contains no usable prices.', false);

        return ['success' => true, 'data' => [
            'points' => $points,
            'source' => 'Yahoo Finance historical market data',
            'provider' => 'yahoo-finance-history',
            'is_local_history' => false,
            'is_delayed' => true,
            'range' => $range,
            'interval' => $interval,
            'currency' => $result['meta']['currency'] ?? null,
            'exchange_timezone' => $result['meta']['exchangeTimezoneName'] ?? null,
        ]];
    }

    private function providerSymbol(string $symbol): string
    {
        $upper = strtoupper(trim($symbol));
        return preg_match('/^[A-Z]+\.[A-Z]$/', $upper) ? str_replace('.', '-', $upper) : $upper;
    }

    private function numberOrNull(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function error(string $code, string $message, bool $retryable): array
    {
        return ['success' => false, 'error_code' => $code, 'message' => $message, 'retryable' => $retryable, 'cached_data_available' => false];
    }
}
