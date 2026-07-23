<?php
declare(strict_types=1);

namespace App\Integrations\OpenRouter;

use App\Config\Env;

final class OpenRouterClient
{
    private const ENDPOINT = 'https://openrouter.ai/api/v1/chat/completions';

    public function configured(): bool
    {
        return trim((string) Env::get('OPENROUTER_API_KEY', '')) !== '';
    }

    public function chat(array $messages, array $schema, ?string $model = null): array
    {
        if (!$this->configured()) return $this->error('OPENROUTER_NOT_CONFIGURED', 'OpenRouter API key is not configured.', false);
        $payload = [
            'model' => $model ?: (string) Env::get('OPENROUTER_MODEL', 'nvidia/nemotron-3-super-120b-a12b:free'),
            'messages' => $messages,
            'temperature' => 0.2,
            'max_tokens' => 900,
            'response_format' => ['type' => 'json_schema', 'json_schema' => ['name' => 'papertrade_prediction', 'strict' => true, 'schema' => $schema]],
        ];
        $curl = curl_init(self::ENDPOINT);
        curl_setopt_array($curl, secure_curl_options([
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . Env::get('OPENROUTER_API_KEY', ''),
                'Content-Type: application/json',
                'Accept: application/json',
                'HTTP-Referer: ' . Env::get('OPENROUTER_SITE_URL', config('app.url')),
                'X-OpenRouter-Title: ' . Env::get('OPENROUTER_SITE_NAME', config('app.name')),
            ],
        ]));
        $body = curl_exec($curl); $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE); $networkError = curl_error($curl); curl_close($curl);
        if ($body === false || $networkError !== '') return $this->error('OPENROUTER_NETWORK_ERROR', 'Could not reach OpenRouter.', true);
        $data = json_decode((string) $body, true);
        if ($status === 429) return $this->error('OPENROUTER_RATE_LIMIT', 'OpenRouter request limit or credit limit reached.', true);
        if (in_array($status, [401,403], true)) return $this->error('OPENROUTER_AUTH_FAILED', 'OpenRouter rejected the API key.', false);
        if ($status >= 400) return $this->error('OPENROUTER_REQUEST_FAILED', is_array($data) ? (string) ($data['error']['message'] ?? "OpenRouter returned HTTP {$status}.") : "OpenRouter returned HTTP {$status}.", $status >= 500);
        $content = $data['choices'][0]['message']['content'] ?? null;
        if (is_array($content)) $content = implode('', array_map(static fn(array $part): string => (string) ($part['text'] ?? ''), $content));
        $json = is_string($content) ? json_decode($content, true) : null;
        if (!is_array($json) && is_string($content)) {
            $candidate = trim($content);
            $candidate = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $candidate) ?? $candidate;
            $firstBrace = strpos($candidate, '{');
            $lastBrace = strrpos($candidate, '}');
            if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
                $candidate = substr($candidate, $firstBrace, $lastBrace - $firstBrace + 1);
            }
            $json = json_decode($candidate, true);
        }
        if (!is_array($json)) return $this->error('OPENROUTER_INVALID_JSON', 'OpenRouter did not return valid structured JSON.', true, ['raw_response_available' => $content !== null]);
        return ['success' => true, 'data' => $json, 'model' => $data['model'] ?? $payload['model'], 'usage' => $data['usage'] ?? null];
    }

    private function error(string $code, string $message, bool $retryable, array $extra = []): array
    {
        return ['success' => false, 'error_code' => $code, 'message' => $message, 'retryable' => $retryable] + $extra;
    }
}
