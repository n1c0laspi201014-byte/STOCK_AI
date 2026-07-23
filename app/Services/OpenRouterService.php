<?php
declare(strict_types=1);

namespace App\Services;

use App\Integrations\OpenRouter\OpenRouterClient;

final class OpenRouterService
{
    public function __construct(private readonly OpenRouterClient $client) {}
    public function configured(): bool { return $this->client->configured(); }

    public function analyze(array $context, ?string $model = null): array
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'news_sentiment_score' => ['type' => 'number', 'minimum' => 0, 'maximum' => 100],
                'signal' => ['type' => 'string', 'enum' => ['buy','hold','sell','watch']],
                'risk_level' => ['type' => 'string', 'enum' => ['low','medium','high']],
                'summary' => ['type' => 'string', 'maxLength' => 500],
                'positive_factors' => ['type' => 'array', 'maxItems' => 5, 'items' => ['type' => 'string', 'maxLength' => 180]],
                'negative_factors' => ['type' => 'array', 'maxItems' => 5, 'items' => ['type' => 'string', 'maxLength' => 180]],
                'invalidation_conditions' => ['type' => 'array', 'maxItems' => 5, 'items' => ['type' => 'string', 'maxLength' => 180]],
                'urgency' => ['type' => 'string', 'enum' => ['safe','watch','urgent']],
            ],
            'required' => ['news_sentiment_score','signal','risk_level','summary','positive_factors','negative_factors','invalidation_conditions','urgency'],
            'additionalProperties' => false,
        ];
        $system = 'You are the explanation component of an educational paper-trading project. Return only the required JSON. Use cautious language such as estimated, suggests, may, could, and uncertain. Never claim guaranteed profit, certainty, risk-free results, or direct professional financial advice. The PHP heuristic, not you, determines displayed probabilities.';
        $messages = [['role' => 'system', 'content' => $system], ['role' => 'user', 'content' => json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]];
        $result = $this->client->chat($messages, $schema, $model);
        if ($result['success'] && $this->valid($result['data'])) return $result;
        if (!($result['success'] ?? false) && ($result['error_code'] ?? '') !== 'OPENROUTER_INVALID_JSON') {
            return $result;
        }
        $repairMessages = $messages;
        $repairMessages[] = ['role' => 'user', 'content' => 'Repair the response and return a JSON object that exactly matches the supplied schema.'];
        $repaired = $this->client->chat($repairMessages, $schema, $model);
        if ($repaired['success'] && $this->valid($repaired['data'])) return $repaired + ['repaired' => true];
        return ['success' => false, 'error_code' => $repaired['error_code'] ?? $result['error_code'] ?? 'OPENROUTER_INVALID_RESPONSE', 'message' => $repaired['message'] ?? $result['message'] ?? 'AI analysis was unavailable.', 'retryable' => $repaired['retryable'] ?? false];
    }

    private function valid(array $data): bool
    {
        if (!is_numeric($data['news_sentiment_score'] ?? null) || (float) $data['news_sentiment_score'] < 0 || (float) $data['news_sentiment_score'] > 100) return false;
        if (!in_array($data['signal'] ?? '', ['buy','hold','sell','watch'], true)) return false;
        if (!in_array($data['risk_level'] ?? '', ['low','medium','high'], true)) return false;
        if (!in_array($data['urgency'] ?? '', ['safe','watch','urgent'], true)) return false;
        foreach (['positive_factors','negative_factors','invalidation_conditions'] as $key) if (!is_array($data[$key] ?? null) || count($data[$key]) > 5) return false;
        $forbidden = '/guaranteed|certain profit|cannot lose|definitely buy|definitely sell|risk[- ]free/i';
        return !preg_match($forbidden, (string) ($data['summary'] ?? ''));
    }
}
