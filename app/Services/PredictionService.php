<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\PortfolioRepository;
use App\Repositories\PredictionRepository;
use App\Repositories\StockRepository;
use App\Repositories\WatchlistRepository;
use App\Repositories\SettingsRepository;
use App\Support\Validator;
use DateTimeImmutable;
use InvalidArgumentException;

final class PredictionService
{
    public function __construct(
        private readonly StockRepository $stocks,
        private readonly PortfolioRepository $portfolios,
        private readonly WatchlistRepository $watchlist,
        private readonly SettingsRepository $settings,
        private readonly PredictionRepository $predictions,
        private readonly MarketDataService $market,
        private readonly TechnicalIndicatorService $indicators,
        private readonly PredictionScoreService $scores,
        private readonly OpenRouterService $ai
    ) {}

    public function generate(int $userId, int|string $stockIdentifier, string $horizon = '7d', bool $includeAi = true): array
    {
        $userSettings = $this->settings->get($userId);
        if ($horizon === '') $horizon = (string) ($userSettings['default_horizon'] ?? '7d');
        $horizon = Validator::oneOf($horizon, ['1d','7d','30d'], 'prediction horizon');
        $stock = is_int($stockIdentifier) || ctype_digit((string) $stockIdentifier) ? $this->stocks->findById((int) $stockIdentifier) : $this->stocks->findBySymbol(Validator::symbol($stockIdentifier));
        if ($stock === null) throw new InvalidArgumentException('Stock not found. Search for it first.');
        $quoteResult = $this->market->quote((string) $stock['symbol'], (string) $stock['exchange_code']);
        if (!$quoteResult['success']) throw new InvalidArgumentException($quoteResult['message'] ?? 'Current quote unavailable.');
        $quote = $quoteResult['data'];
        $historyResult = $this->market->history((string) $stock['symbol'], '3m');
        $points = $historyResult['success'] ? ($historyResult['data']['points'] ?? []) : [];
        if (count($points) < 2 && (float) ($quote['previous_close'] ?? 0) > 0) {
            $points = [
                ['timestamp' => date(DATE_ATOM, strtotime('-1 day')), 'close' => (float) $quote['previous_close']],
                ['timestamp' => (string) ($quote['provider_timestamp'] ?? date(DATE_ATOM)), 'close' => (float) $quote['price']],
            ];
        }
        $technical = $this->indicators->calculate($points);
        if (!$technical['available']) throw new InvalidArgumentException('Insufficient market data to estimate a probability.');
        $newsResult = ['success' => false, 'data' => [], 'message' => $includeAi ? 'News unavailable.' : 'Deferred for automatic first estimate.'];
        $marketStatus = $this->market->marketStatus((string) ($stock['exchange_code'] ?: 'US'));
        $marketScore = $marketStatus['success'] ? (($marketStatus['data']['status'] ?? 'unknown') === 'open' ? 55.0 : 50.0) : null;
        $owned = $this->portfolios->holding($userId, (int) $stock['id']) !== null;
        $aiResult = ['success' => false, 'message' => 'AI explanation is not configured.'];
        if ($includeAi && !empty($userSettings['news_analysis_enabled'])) {
            $newsResult = $this->market->news((string) $stock['symbol']);
        }
        if ($includeAi && $this->ai->configured() && !empty($userSettings['news_analysis_enabled'])) {
            $aiResult = $this->ai->analyze([
                'stock' => ['symbol' => $stock['symbol'], 'company_name' => $stock['company_name'], 'industry' => $stock['industry']],
                'quote' => $quote,
                'technical_indicators' => $technical['indicators'],
                'technical_score' => $technical['score'],
                'market_score' => $marketScore,
                'news' => $newsResult['success'] ? array_slice($newsResult['data'], 0, (int) ($userSettings['max_news_items'] ?? 5)) : [],
                'position' => ['owned' => $owned],
                'horizon' => $horizon,
                'disclaimer' => config('app.disclaimer'),
            ], (string) ($userSettings['ai_model'] ?? config('prediction.model')));
        }
        $newsScore = $aiResult['success'] ? (float) $aiResult['data']['news_sentiment_score'] : null;
        $combined = $this->scores->combine((float) $technical['score'], $newsScore, $marketScore);
        if (!$combined['available']) throw new InvalidArgumentException('Insufficient data to estimate a probability.');
        $completeness = 100 - (count($combined['missing']) * 20);
        $confidence = max(10, min(95, ((float) $technical['confidence'] * .7) + ($completeness * .3)));
        $volatility = (float) ($technical['indicators']['volatility'] ?? 0);
        $risk = $aiResult['success'] ? $aiResult['data']['risk_level'] : ($volatility > 4 ? 'high' : ($volatility > 2 ? 'medium' : 'low'));
        $signal = $this->scores->signal((float) $combined['probability_up'], $confidence, $risk, $owned);
        $status = ($combined['missing'] !== [] || !$historyResult['success'] || !$aiResult['success']) ? 'partial' : 'fresh';
        $summary = $aiResult['success'] ? (string) $aiResult['data']['summary'] : 'Technical signals suggest a ' . strtolower($signal) . ' posture, but AI/news context was unavailable; treat this estimate cautiously.';
        $days = ['1d' => 1, '7d' => 7, '30d' => 30][$horizon];
        $prediction = [
            'horizon' => $horizon,
            'signal' => $signal,
            'estimated_probability_up' => $combined['probability_up'],
            'estimated_probability_down' => $combined['probability_down'],
            'confidence_score' => round($confidence, 2),
            'risk_level' => $risk,
            'technical_score' => $technical['score'],
            'news_score' => $newsScore,
            'market_score' => $marketScore,
            'summary' => $summary,
            'positive_factors' => array_slice(array_values(array_unique(array_merge($technical['positive_factors'], $aiResult['success'] ? $aiResult['data']['positive_factors'] : []))), 0, 5),
            'negative_factors' => array_slice(array_values(array_unique(array_merge($technical['negative_factors'], $aiResult['success'] ? $aiResult['data']['negative_factors'] : ['AI/news context unavailable.']))), 0, 5),
            'invalidation_conditions' => $aiResult['success'] ? $aiResult['data']['invalidation_conditions'] : ['A material price or news change may invalidate this estimate.'],
            'source_data_timestamp' => date('Y-m-d H:i:s', strtotime((string) ($quote['provider_timestamp'] ?? 'now'))),
            'model_name' => $aiResult['model'] ?? 'deterministic-technical-fallback',
            'status' => $status,
            'expires_at' => (new DateTimeImmutable('+' . min($days, 1) * 6 . ' hours'))->format('Y-m-d H:i:s'),
            'start_price' => (float) $quote['price'],
            'missing_components' => $combined['missing'],
            'quote' => $quote,
        ];
        $prediction['id'] = $this->predictions->save($userId, (int) $stock['id'], $prediction);
        $prediction['stock_id'] = (int) $stock['id']; $prediction['symbol'] = $stock['symbol']; $prediction['company_name'] = $stock['company_name'];
        return $prediction;
    }

    public function latestOrNull(int $userId, int $stockId): ?array { return $this->predictions->latest($userId, $stockId); }
    public function owned(int $userId): array { return $this->predictions->forOwned($userId); }
    public function watchlisted(int $userId): array { return $this->predictions->forWatchlist($userId); }
    public function history(int $userId, ?int $stockId = null): array { return $this->predictions->history($userId, $stockId); }

    public function generateMissingForUser(int $userId, int $limit = 10): array
    {
        $ids = [];
        foreach ($this->portfolios->holdings($userId) as $holding) $ids[(int) $holding['stock_id']] = true;
        foreach ($this->watchlist->all($userId) as $item) $ids[(int) $item['stock_id']] = true;

        $generated = 0;
        $failed = [];
        foreach (array_slice(array_keys($ids), 0, max(1, min(25, $limit))) as $stockId) {
            if ($this->predictions->latest($userId, (int) $stockId) !== null) continue;
            try {
                $this->generate($userId, (int) $stockId, (string) ($this->settings->get($userId)['default_horizon'] ?? '7d'), false);
                $generated++;
            } catch (\Throwable $exception) {
                $stock = $this->stocks->findById((int) $stockId);
                $failed[] = [
                    'stock_id' => (int) $stockId,
                    'symbol' => (string) ($stock['symbol'] ?? $stockId),
                    'message' => $exception->getMessage(),
                ];
            }
        }
        return ['generated' => $generated, 'failed' => $failed, 'covered_stock_ids' => array_map('intval', array_keys($ids))];
    }

    public function discover(int $userId, bool $generateMissing = false): array
    {
        $holdings = array_column($this->portfolios->holdings($userId), 'stock_id');
        $watchlist = array_column($this->watchlist->all($userId), 'stock_id');
        $results = [];
        foreach ($this->stocks->demoUniverse() as $stock) {
            if (in_array($stock['id'], $holdings) || in_array($stock['id'], $watchlist)) continue;
            $prediction = $this->predictions->latest($userId, (int) $stock['id']);
            if ($prediction === null && $generateMissing) {
                try { $prediction = $this->generate($userId, (int) $stock['id'], (string) ($this->settings->get($userId)['default_horizon'] ?? '7d'), false); } catch (\Throwable) { $prediction = null; }
            }
            $results[] = ['stock' => $stock, 'prediction' => $prediction, 'selection_reason' => 'Liquid stock from the configured school-project candidate universe.'];
            if (count($results) >= (int) config('prediction.max_discovery_results', 5)) break;
        }
        return $results;
    }
}
