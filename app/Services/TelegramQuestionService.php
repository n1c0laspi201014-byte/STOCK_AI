<?php
declare(strict_types=1);

namespace App\Services;

use App\Integrations\Telegram\TelegramTestClient;
use App\Repositories\StockRepository;
use PDO;
use RuntimeException;

final class TelegramQuestionService
{
    private const OFFSET_KEY = 'telegram_question_update_offset';
    private const MAX_MESSAGE_AGE_SECONDS = 300;

    public function __construct(
        private readonly PDO $pdo,
        private readonly TelegramTestClient $telegram,
        private readonly StockRepository $stocks,
        private readonly MarketDataService $market,
        private readonly PredictionService $predictions
    ) {}

    public function poll(): array
    {
        $storedOffset = $this->setting(self::OFFSET_KEY);
        if ($storedOffset === null) {
            $latest = $this->telegram->updates(-1, 1);
            if (!($latest['success'] ?? false)) throw new RuntimeException((string) ($latest['message'] ?? 'Telegram update polling failed.'));
            $updates = $latest['data'] ?? [];
            $offset = $updates === [] ? 0 : ((int) end($updates)['update_id'] + 1);
            $this->saveSetting(self::OFFSET_KEY, (string) $offset);
            return [];
        }

        $result = $this->telegram->updates((int) $storedOffset, 25);
        if (!($result['success'] ?? false)) throw new RuntimeException((string) ($result['message'] ?? 'Telegram update polling failed.'));
        $answers = [];
        $nextOffset = (int) $storedOffset;
        foreach ($result['data'] ?? [] as $update) {
            $updateId = (int) ($update['update_id'] ?? 0);
            $nextOffset = max($nextOffset, $updateId + 1);
            $message = $update['message'] ?? null;
            if (!is_array($message) || !isset($message['chat']['id']) || !is_string($message['text'] ?? null)) continue;
            $this->saveSetting(self::OFFSET_KEY, (string) $nextOffset);
            $messageTimestamp = (int) ($message['date'] ?? 0);
            if ($messageTimestamp > 0 && time() - $messageTimestamp > self::MAX_MESSAGE_AGE_SECONDS) continue;
            $question = (string) $message['text'];
            $answer = $this->answer((string) $message['chat']['id'], $question);
            $answer['question'] = $question;
            $answer['factual_answer'] = (string) $answer['message'];
            $answer['has_stock_context'] = $answer['symbol'] !== null;
            $answer['fast_path'] = $this->isFastCommand($question) || $answer['symbol'] === null;
            $answer['intent'] = $this->wantsNews($question) ? 'news' : 'quote';
            $answer['message_age_seconds'] = $messageTimestamp > 0 ? max(0, time() - $messageTimestamp) : null;
            $answer['update_id'] = $updateId;
            $answers[] = $answer;
        }
        if ($nextOffset !== (int) $storedOffset) $this->saveSetting(self::OFFSET_KEY, (string) $nextOffset);
        return $answers;
    }

    public function answer(string $chatId, string $text): array
    {
        $connection = $this->connection($chatId);
        if ($connection === null) {
            return [
                'chat_id' => $chatId,
                'user_id' => null,
                'symbol' => null,
                'message' => "STOCK AI does not recognize this chat yet.\n\nOpen Setup > Telegram, save chat ID {$chatId}, then send /stock AAPL again.",
            ];
        }

        $resolution = $this->resolve($text);
        if (!($resolution['success'] ?? false)) {
            return [
                'chat_id' => $chatId,
                'user_id' => (int) $connection['user_id'],
                'symbol' => null,
                'message' => (string) $resolution['message'],
            ];
        }
        $symbol = (string) $resolution['symbol'];

        $quoteResult = $this->market->quote($symbol);
        if (!($quoteResult['success'] ?? false)) {
            return [
                'chat_id' => $chatId,
                'user_id' => (int) $connection['user_id'],
                'symbol' => $symbol,
                'message' => "{$symbol}: a current market quote is unavailable right now. Please try again later.",
            ];
        }

        $stock = $this->stocks->findBySymbol($symbol);
        $quote = $quoteResult['data'];
        $prediction = $stock !== null
            ? $this->predictions->latestOrNull((int) $connection['user_id'], (int) $stock['id'])
            : null;

        $company = (string) ($stock['company_name'] ?? $resolution['company_name'] ?? $symbol);
        $currency = (string) ($quote['currency'] ?? $stock['currency'] ?? 'USD');
        $change = isset($quote['change_percent']) ? number_format((float) $quote['change_percent'], 2) . '%' : 'unavailable';
        $requested = (string) ($resolution['query'] ?? $symbol);
        $matchLine = $this->canonical($requested) === $this->canonical($symbol) ? '' : "Matched request: {$requested} -> {$symbol}\n";
        $estimate = $prediction === null
            ? "Estimate: no saved estimate is available yet"
            : 'Estimate: ' . strtoupper((string) $prediction['signal']) . "\nEstimated chance up: " . number_format((float) $prediction['estimated_probability_up'], 1) . '% over ' . $prediction['horizon'] . "\nConfidence: " . number_format((float) $prediction['confidence_score'], 1) . "%\nRisk: " . ucfirst((string) $prediction['risk_level']) . "\nPrediction updated: " . (string) ($prediction['generated_at'] ?? 'unknown') . "\nReason: " . (string) $prediction['summary'];
        $news = $this->wantsNews($text) ? $this->newsContext($symbol) : '';

        return [
            'chat_id' => $chatId,
            'user_id' => (int) $connection['user_id'],
            'symbol' => $symbol,
            'message' => "STOCK AI - {$symbol}\n{$company}\n{$matchLine}\nCurrent price: " . number_format((float) $quote['price'], 2) . " {$currency}\nSession change: {$change}\nMarket: " . ($quote['market_status'] ?? 'unknown') . "\nProvider: " . ($quote['provider'] ?? 'unknown') . "\nUpdated: " . ($quote['provider_timestamp'] ?? 'unknown') . "\n\n{$estimate}{$news}\n\n" . config('app.disclaimer'),
        ];
    }

    public function resolve(string $text): array
    {
        $query = $this->queryFrom($text);
        if ($query === null) {
            return [
                'success' => false,
                'message' => "Ask with a company name or ticker.\n\nExamples:\n/stock NVIDIA\nApple\nwhat about Tesla?\n\$MSFT\n\nIf a name has several matches, I will show the tickers to choose from.",
            ];
        }

        $canonical = $this->canonical($query);
        $aliases = [
            'alphabet' => 'GOOGL',
            'amazon' => 'AMZN',
            'apple' => 'AAPL',
            'berkshire hathaway' => 'BRK.A',
            'coca cola' => 'KO',
            'coca cola company' => 'KO',
            'disney' => 'DIS',
            'facebook' => 'META',
            'google' => 'GOOGL',
            'meta' => 'META',
            'meta platforms' => 'META',
            'microsoft' => 'MSFT',
            'netflix' => 'NFLX',
            'nike' => 'NKE',
            'nvidia' => 'NVDA',
            'tesla' => 'TSLA',
        ];
        if (isset($aliases[$canonical])) {
            return ['success' => true, 'query' => $query, 'symbol' => $aliases[$canonical], 'company_name' => $query, 'matched_by' => 'common-name'];
        }

        $explicitTicker = str_starts_with(ltrim($text), '$');
        if ($explicitTicker && preg_match('/^[A-Za-z][A-Za-z0-9.:-]{0,31}$/', $query)) {
            return ['success' => true, 'query' => $query, 'symbol' => strtoupper($query), 'company_name' => strtoupper($query), 'matched_by' => 'ticker'];
        }

        $search = $this->market->search($query);
        $candidates = array_values(array_filter(
            is_array($search['data'] ?? null) ? $search['data'] : [],
            static fn(array $candidate): bool => preg_match('/^[A-Za-z][A-Za-z0-9.:-]{0,31}$/', (string) ($candidate['symbol'] ?? '')) === 1
        ));
        if ($candidates === []) {
            $local = $this->stocks->searchLocal($query, 5);
            $candidates = array_map(static fn(array $stock): array => [
                'symbol' => $stock['symbol'],
                'company_name' => $stock['company_name'],
                'exchange' => $stock['exchange_code'],
                'country' => $stock['country'],
            ], $local);
        }
        if ($candidates === [] && preg_match('/^[A-Za-z][A-Za-z0-9.:-]{0,15}$/', $query)) {
            return ['success' => true, 'query' => $query, 'symbol' => strtoupper($query), 'company_name' => strtoupper($query), 'matched_by' => 'ticker-fallback'];
        }
        if ($candidates === []) {
            return [
                'success' => false,
                'message' => "I could not find a listed company matching \"{$query}\".\n\nTry its full company name, check the spelling, or send a ticker such as /stock NVDA.",
            ];
        }

        foreach ($candidates as $index => &$candidate) {
            $candidate['_score'] = $this->matchScore($candidate, $query) - $index;
        }
        unset($candidate);
        usort($candidates, static fn(array $left, array $right): int => $right['_score'] <=> $left['_score']);
        $top = $candidates[0];
        $runnerUp = $candidates[1] ?? null;
        $decisive = count($candidates) === 1 || (int) $top['_score'] >= 880 || ($runnerUp !== null && (int) $top['_score'] - (int) $runnerUp['_score'] >= 100);
        if (!$decisive) {
            $lines = ["I found several matches for \"{$query}\":", ''];
            foreach (array_slice($candidates, 0, 5) as $candidate) {
                $exchange = trim((string) ($candidate['exchange'] ?? ''));
                $lines[] = strtoupper((string) $candidate['symbol']) . ' - ' . (string) ($candidate['company_name'] ?? $candidate['symbol']) . ($exchange !== '' ? " ({$exchange})" : '');
            }
            $lines[] = '';
            $lines[] = 'Reply with one ticker, for example /stock ' . strtoupper((string) $top['symbol']) . '.';
            return ['success' => false, 'message' => implode("\n", $lines), 'matches' => array_slice($candidates, 0, 5)];
        }

        return [
            'success' => true,
            'query' => $query,
            'symbol' => strtoupper((string) $top['symbol']),
            'company_name' => (string) ($top['company_name'] ?? $top['symbol']),
            'matched_by' => 'provider-search',
        ];
    }

    private function newsContext(string $symbol): string
    {
        $result = $this->market->news($symbol);
        $items = is_array($result['data'] ?? null) ? $result['data'] : [];
        $cutoff = time() - 7 * 86400;
        $recent = array_values(array_filter($items, static function (array $item) use ($cutoff): bool {
            $timestamp = strtotime((string) ($item['datetime'] ?? ''));
            return $timestamp !== false && $timestamp >= $cutoff;
        }));
        if ($recent === []) return "\n\nRecent news: no provider headline from the last 7 days is available.";
        $lines = ["", "", 'Recent provider news (last 7 days):'];
        foreach (array_slice($recent, 0, 3) as $item) {
            $date = date('d M Y H:i', strtotime((string) $item['datetime']));
            $headline = trim((string) ($item['headline'] ?? 'Untitled headline'));
            $source = trim((string) ($item['source'] ?? 'provider'));
            $lines[] = "- {$date} - {$headline} ({$source})";
        }
        return implode("\n", $lines);
    }

    private function isFastCommand(string $text): bool
    {
        $text = trim($text);
        return preg_match('/^\/(?:stock|start|help)\b/i', $text) === 1
            || preg_match('/^\$[A-Za-z][A-Za-z0-9.:-]{0,31}$/', $text) === 1;
    }

    private function wantsNews(string $text): bool
    {
        return preg_match('/\b(?:news|new|headline|headlines|latest|happening)\b/i', $text) === 1;
    }

    private function queryFrom(string $text): ?string
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? '');
        if ($text === '' || preg_match('/^\/(?:start|help)\b/i', $text)) return null;
        if (preg_match('/^\/stock(?:@[A-Za-z0-9_]+)?(?:\s+(.+))?$/i', $text, $matches)) {
            $text = trim((string) ($matches[1] ?? ''));
        } elseif (preg_match('/^stock\s+(.+)$/i', $text, $matches)) {
            $text = trim($matches[1]);
        } elseif (str_starts_with($text, '/')) {
            return null;
        }
        if (str_starts_with($text, '$')) $text = substr($text, 1);
        if (preg_match('/^(?:what(?:\'s| is)\s+new\s+(?:with|about)\s+|what\s+is\s+happening\s+(?:with|to)\s+|latest\s+(?:news|headlines?)\s+(?:on|about|for)\s+|(?:news|headlines?)\s+(?:on|about|for)\s+)(.+)$/i', $text, $matches)) {
            $text = trim($matches[1]);
        }
        $text = preg_replace('/^(?:what(?:\'s| is)\s+(?:the\s+)?(?:stock|price)\s+(?:of|for)\s+|what about\s+|how is\s+|tell me about\s+|quote for\s+|price of\s+)/i', '', $text) ?? $text;
        $text = preg_replace('/\s+(?:stock|shares?|today|doing|news|headlines?)\s*[?.!]*$/i', '', $text) ?? $text;
        $text = trim($text, " \t\n\r\0\x0B?!,;");
        return $text !== '' && mb_strlen($text) <= 100 ? $text : null;
    }

    private function matchScore(array $candidate, string $query): int
    {
        $symbol = strtoupper((string) ($candidate['symbol'] ?? ''));
        $queryUpper = strtoupper(trim($query));
        if ($symbol === $queryUpper) return 1000;
        $company = $this->canonical((string) ($candidate['company_name'] ?? ''));
        $wanted = $this->canonical($query);
        $score = 0;
        if ($company === $wanted) $score = 900;
        elseif (str_starts_with($company, $wanted . ' ')) $score = 800;
        elseif (str_contains(' ' . $company . ' ', ' ' . $wanted . ' ')) $score = 700;
        elseif (str_contains($company, $wanted)) $score = 500;
        if (strtoupper((string) ($candidate['country'] ?? '')) === 'US') $score += 30;
        if (in_array(strtoupper((string) ($candidate['exchange'] ?? '')), ['NASDAQ', 'NYSE', 'NYSE ARCA', 'AMEX'], true)) $score += 20;
        if (!str_contains($symbol, '.')) $score += 10;
        return $score;
    }

    private function canonical(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(['&', '.com'], [' and ', ' '], $value);
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?? $value;
        $words = preg_split('/\s+/', trim($value)) ?: [];
        $suffixes = ['inc', 'incorporated', 'corp', 'corporation', 'company', 'co', 'plc', 'ltd', 'limited'];
        while ($words !== [] && in_array(end($words), $suffixes, true)) array_pop($words);
        return implode(' ', $words);
    }

    private function connection(string $chatId): ?array
    {
        $statement = $this->pdo->prepare('SELECT tc.user_id, u.name FROM telegram_connections tc JOIN users u ON u.id=tc.user_id WHERE tc.chat_id=:chat_id AND tc.is_enabled=1 AND tc.is_verified=1 AND u.is_active=1 LIMIT 1');
        $statement->execute(['chat_id' => $chatId]);
        return $statement->fetch() ?: null;
    }

    private function setting(string $key): ?string
    {
        $statement = $this->pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key=:setting_key');
        $statement->execute(['setting_key' => $key]);
        $value = $statement->fetchColumn();
        return is_string($value) ? $value : null;
    }

    private function saveSetting(string $key, string $value): void
    {
        $statement = $this->pdo->prepare('INSERT INTO system_settings (setting_key, setting_value) VALUES (:setting_key, :setting_value) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
        $statement->execute(['setting_key' => $key, 'setting_value' => $value]);
    }
}
