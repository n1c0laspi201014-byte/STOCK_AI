<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\AlertEventRepository;
use App\Repositories\AlertRuleRepository;
use App\Repositories\PredictionRepository;
use PDO;
use RuntimeException;
use Throwable;

final class AlertService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly AlertRuleRepository $rules,
        private readonly AlertEventRepository $events,
        private readonly PredictionRepository $predictions,
        private readonly PredictionService $predictionService,
        private readonly MarketDataService $market
    ) {}

    public function dueRules(): array { return $this->rules->due(); }

    public function evaluate(int $ruleId, bool $forceTest = false): array
    {
        $statement = $this->pdo->prepare('SELECT a.*, s.symbol, s.exchange_code, s.currency, tc.chat_id, us.timezone, us.quiet_hours_enabled, us.quiet_hours_start, us.quiet_hours_end, us.max_alerts_per_day FROM alert_rules a JOIN stocks s ON s.id=a.stock_id JOIN user_settings us ON us.user_id=a.user_id LEFT JOIN telegram_connections tc ON tc.user_id=a.user_id AND tc.is_enabled=1 WHERE a.id=:id');
        $statement->execute(['id' => $ruleId]);
        $rule = $statement->fetch() ?: throw new RuntimeException('Alert rule not found.');
        $this->pdo->prepare('UPDATE alert_rules SET last_checked_at=NOW() WHERE id=:id')->execute(['id' => $ruleId]);
        if (!$forceTest && empty($rule['is_enabled'])) return ['triggered' => false, 'reason' => 'Rule is disabled.'];
        if (!$forceTest && $rule['last_alert_at'] && strtotime((string) $rule['last_alert_at']) > time() - ((int) $rule['cooldown_minutes'] * 60)) return ['triggered' => false, 'reason' => 'Cooldown is active.'];
        if (!$forceTest && $this->events->countToday((int) $rule['user_id']) >= (int) $rule['max_alerts_per_day']) return ['triggered' => false, 'reason' => 'Daily alert maximum reached.'];
        if (!$forceTest && $this->inQuietHours($rule)) return ['triggered' => false, 'reason' => 'Quiet hours are active.'];
        $quoteResult = $this->market->quote((string) $rule['symbol'], (string) $rule['exchange_code'], true);
        if (!$quoteResult['success']) return ['triggered' => false, 'reason' => $quoteResult['message'] ?? 'Quote unavailable.'];
        $quote = $quoteResult['data'];
        if (!$forceTest && !empty($rule['market_hours_only']) && ($quote['market_status'] ?? 'unknown') !== 'open') return ['triggered' => false, 'reason' => 'Market-hours-only rule; market is not open.'];
        $reference = $this->referencePrice($rule, $quote);
        if ($reference <= 0) return ['triggered' => false, 'reason' => 'Reference price unavailable.'];
        $current = (float) $quote['price'];
        $change = $current - $reference;
        $changePercent = ($change / $reference) * 100;
        $triggered = $forceTest || $this->crossed($rule, $current, $change, $changePercent);
        if (!$triggered) return ['triggered' => false, 'reason' => 'Threshold not crossed.', 'change_percent' => $changePercent];
        // A prediction belongs to the alert that is about to be delivered. Generate it
        // only after the configured price threshold is crossed so there is no separate
        // background prediction agent and no unnecessary AI/API traffic.
        try {
            $prediction = $this->predictionService->generate(
                (int) $rule['user_id'],
                (int) $rule['stock_id']
            );
        } catch (Throwable) {
            // A recent saved estimate is safer than omitting context completely when an
            // external provider has a temporary failure.
            $prediction = $this->predictions->latest((int) $rule['user_id'], (int) $rule['stock_id']);
        }
        $confidence = (float) ($prediction['confidence_score'] ?? 0);
        if (!$forceTest && $confidence < (float) $rule['minimum_confidence']) return ['triggered' => false, 'reason' => 'Prediction confidence is below the rule minimum.'];
        $urgency = abs($changePercent) >= max(1, (float) $rule['threshold_value'] * 2) && $confidence >= 70 ? 'urgent' : (abs($changePercent) >= (float) $rule['threshold_value'] ? 'watch' : 'safe');
        $message = $this->message($rule, $quote, $reference, $changePercent, $prediction, $urgency, $forceTest);
        $eventId = $this->events->create(['alert_rule_id' => $ruleId, 'user_id' => $rule['user_id'], 'stock_id' => $rule['stock_id'], 'reference_price' => $reference, 'current_price' => $current, 'change_amount' => $change, 'change_percent' => $changePercent, 'prediction_id' => $prediction['id'] ?? null, 'urgency' => $urgency, 'message' => $message]);
        $this->pdo->prepare('UPDATE alert_rules SET last_alert_at=NOW(),last_alert_price=:price WHERE id=:id')->execute(['price' => $current, 'id' => $ruleId]);
        return ['triggered' => true, 'event_id' => $eventId, 'chat_id' => $rule['chat_id'], 'message' => $message, 'urgency' => $urgency, 'quote' => $quote];
    }

    private function referencePrice(array $rule, array $quote): float
    {
        return match ($rule['reference_type']) {
            'previous_close' => (float) ($quote['previous_close'] ?? 0),
            'fixed_price' => (float) ($rule['reference_price'] ?? 0),
            'average_cost' => $this->averageCost((int) $rule['user_id'], (int) $rule['stock_id']),
            default => (float) ($rule['last_alert_price'] ?: $rule['reference_price'] ?: $quote['previous_close'] ?: $quote['price']),
        };
    }

    private function averageCost(int $userId, int $stockId): float
    {
        $statement = $this->pdo->prepare('SELECT h.average_cost FROM holdings h JOIN portfolios p ON p.id=h.portfolio_id WHERE p.user_id=:user_id AND h.stock_id=:stock_id');
        $statement->execute(['user_id' => $userId, 'stock_id' => $stockId]);
        return (float) ($statement->fetchColumn() ?: 0);
    }

    private function crossed(array $rule, float $current, float $change, float $percent): bool
    {
        $value = (float) $rule['threshold_value'];
        if ($rule['threshold_type'] === 'target_price') return $rule['direction'] === 'decrease' ? $current <= $value : ($rule['direction'] === 'both' ? abs($current - (float) $rule['reference_price']) >= abs($value - (float) $rule['reference_price']) : $current >= $value);
        $movement = $rule['threshold_type'] === 'absolute_price' ? $change : $percent;
        return match ($rule['direction']) { 'increase' => $movement >= $value, 'decrease' => $movement <= -$value, default => abs($movement) >= $value };
    }

    private function inQuietHours(array $rule): bool
    {
        if (empty($rule['quiet_hours_enabled']) || !$rule['quiet_hours_start'] || !$rule['quiet_hours_end']) return false;
        $zone = new \DateTimeZone((string) $rule['timezone']); $now = new \DateTimeImmutable('now', $zone); $time = $now->format('H:i:s');
        $start = (string) $rule['quiet_hours_start']; $end = (string) $rule['quiet_hours_end'];
        return $start <= $end ? ($time >= $start && $time < $end) : ($time >= $start || $time < $end);
    }

    private function message(array $rule, array $quote, float $reference, float $changePercent, ?array $prediction, string $urgency, bool $test): string
    {
        $threshold = $rule['threshold_type'] === 'percent' ? $rule['threshold_value'] . '%' : $rule['threshold_value'] . ' ' . $rule['currency'];
        $heading = $test ? '🧪 TEST PRICE ALERT' : '⚠️ PRICE ALERT';
        return $heading . ' — ' . $rule['symbol'] . "\n\n" .
            'Current price: ' . number_format((float) $quote['price'], 2) . ' ' . $rule['currency'] . "\n" .
            'Movement: ' . number_format($changePercent, 2) . "%\nReference: " . number_format($reference, 2) . "\nThreshold: {$threshold}\n" .
            'Market status: ' . ($quote['market_status'] ?? 'unknown') . "\nPrice updated: " . ($quote['provider_timestamp'] ?? 'unknown') . "\n\n" .
            'AI signal: ' . strtoupper((string) ($prediction['signal'] ?? 'unavailable')) . "\nUrgency: " . strtoupper($urgency) . "\n" .
            'Estimated probability: ' . ($prediction ? number_format((float) $prediction['estimated_probability_up'], 1) . '% over ' . $prediction['horizon'] : 'unavailable') . "\n" .
            'Confidence: ' . ($prediction ? number_format((float) $prediction['confidence_score'], 1) . '%' : 'unavailable') . "\nRisk: " . ucfirst((string) ($prediction['risk_level'] ?? 'unknown')) . "\n\n" .
            'Reason: ' . ($prediction['summary'] ?? 'No prediction is available; this alert is based only on the price rule.') . "\n\n" . config('app.disclaimer');
    }
}
