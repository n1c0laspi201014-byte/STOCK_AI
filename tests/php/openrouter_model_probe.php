<?php
declare(strict_types=1);

use App\Config\Env;

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';

$model = trim((string) ($argv[1] ?? ''));
if ($model === '') {
    fwrite(STDERR, "Usage: php openrouter_model_probe.php <model-id> [plain|json]\n");
    exit(2);
}

$mode = strtolower(trim((string) ($argv[2] ?? 'plain')));
$payload = [
    'model' => $model,
    'messages' => [
        [
            'role' => 'system',
            'content' => $mode === 'json'
                ? 'Return only a JSON object with one string field named status.'
                : 'Reply with exactly: STOCK AI ready',
        ],
        ['role' => 'user', 'content' => 'Connection test.'],
    ],
    'temperature' => 0,
    'max_tokens' => 50,
];
if ($mode === 'json') {
    $payload['response_format'] = ['type' => 'json_object'];
}

$curl = curl_init('https://openrouter.ai/api/v1/chat/completions');
curl_setopt_array($curl, secure_curl_options([
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_SLASHES),
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_TIMEOUT => 15,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . Env::get('OPENROUTER_API_KEY', ''),
        'Content-Type: application/json',
        'Accept: application/json',
        'HTTP-Referer: ' . Env::get('OPENROUTER_SITE_URL', config('app.url')),
        'X-OpenRouter-Title: STOCK AI',
    ],
]));

$started = microtime(true);
$body = curl_exec($curl);
$elapsed = microtime(true) - $started;
$status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
$networkError = curl_error($curl);
curl_close($curl);

$decoded = is_string($body) ? json_decode($body, true) : null;
$content = is_array($decoded) ? ($decoded['choices'][0]['message']['content'] ?? null) : null;
$success = $status >= 200 && $status < 300 && is_string($content) && trim($content) !== '';

echo 'SUCCESS=' . ($success ? 'true' : 'false') . PHP_EOL;
echo 'REQUESTED_MODEL=' . $model . PHP_EOL;
echo 'RETURNED_MODEL=' . (string) ($decoded['model'] ?? 'none') . PHP_EOL;
echo 'MODE=' . $mode . PHP_EOL;
echo 'HTTP=' . $status . PHP_EOL;
echo 'NETWORK_ERROR=' . ($networkError === '' ? 'none' : $networkError) . PHP_EOL;
echo 'API_ERROR=' . (string) ($decoded['error']['message'] ?? 'none') . PHP_EOL;
echo 'SECONDS=' . number_format($elapsed, 3) . PHP_EOL;
echo 'HAS_CONTENT=' . (is_string($content) && trim($content) !== '' ? 'true' : 'false') . PHP_EOL;

exit($success ? 0 : 1);
