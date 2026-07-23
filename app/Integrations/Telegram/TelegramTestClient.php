<?php
declare(strict_types=1);

namespace App\Integrations\Telegram;

use App\Config\Env;

final class TelegramTestClient
{
    public function configured(): bool { return trim((string) Env::get('TELEGRAM_BOT_TOKEN', '')) !== ''; }

    public function send(string $chatId, string $message): array
    {
        $token = (string) Env::get('TELEGRAM_BOT_TOKEN', '');
        if ($token === '') return ['success' => false, 'error_code' => 'TELEGRAM_NOT_CONFIGURED', 'message' => 'TELEGRAM_BOT_TOKEN is not configured in .env.', 'retryable' => false];
        if (trim($chatId) === '') return ['success' => false, 'error_code' => 'TELEGRAM_CHAT_REQUIRED', 'message' => 'Telegram chat ID is required.', 'retryable' => false];
        $curl = curl_init('https://api.telegram.org/bot' . rawurlencode($token) . '/sendMessage');
        curl_setopt_array($curl, secure_curl_options([CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query(['chat_id' => $chatId, 'text' => $message, 'disable_web_page_preview' => 'true']), CURLOPT_TIMEOUT => 20, CURLOPT_CONNECTTIMEOUT => 5]));
        $body = curl_exec($curl); $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE); $error = curl_error($curl); curl_close($curl);
        if ($body === false || $error !== '') return ['success' => false, 'error_code' => 'TELEGRAM_NETWORK_ERROR', 'message' => 'Could not reach Telegram.', 'retryable' => true];
        $data = json_decode((string) $body, true);
        if ($status >= 400 || !is_array($data) || empty($data['ok'])) return ['success' => false, 'error_code' => 'TELEGRAM_SEND_FAILED', 'message' => is_array($data) ? (string) ($data['description'] ?? 'Telegram send failed.') : 'Telegram returned an invalid response.', 'retryable' => $status >= 500];
        return ['success' => true, 'message' => 'Telegram message sent.', 'message_id' => $data['result']['message_id'] ?? null];
    }

    public function updates(int $offset = 0, int $limit = 25): array
    {
        $token = (string) Env::get('TELEGRAM_BOT_TOKEN', '');
        if ($token === '') return ['success' => false, 'error_code' => 'TELEGRAM_NOT_CONFIGURED', 'message' => 'TELEGRAM_BOT_TOKEN is not configured in .env.', 'retryable' => false];
        $query = http_build_query([
            'offset' => $offset,
            'limit' => max(1, min(100, $limit)),
            'timeout' => 0,
            'allowed_updates' => json_encode(['message']),
        ]);
        $curl = curl_init('https://api.telegram.org/bot' . rawurlencode($token) . '/getUpdates?' . $query);
        curl_setopt_array($curl, secure_curl_options([CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 20, CURLOPT_CONNECTTIMEOUT => 5]));
        $body = curl_exec($curl); $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE); $error = curl_error($curl); curl_close($curl);
        if ($body === false || $error !== '') return ['success' => false, 'error_code' => 'TELEGRAM_NETWORK_ERROR', 'message' => 'Could not reach Telegram.', 'retryable' => true];
        $data = json_decode((string) $body, true);
        if ($status >= 400 || !is_array($data) || empty($data['ok'])) return ['success' => false, 'error_code' => 'TELEGRAM_UPDATES_FAILED', 'message' => is_array($data) ? (string) ($data['description'] ?? 'Telegram update polling failed.') : 'Telegram returned an invalid response.', 'retryable' => $status >= 500];
        return ['success' => true, 'data' => is_array($data['result'] ?? null) ? $data['result'] : []];
    }
}
