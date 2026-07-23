<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$workflow = json_decode(
    (string) file_get_contents($root . '/n8n/workflows/papertrade-telegram-hub.json'),
    true,
    512,
    JSON_THROW_ON_ERROR
);
$nodes = $workflow['nodes'] ?? [];
$byName = [];
foreach ($nodes as $node) {
    $byName[(string) ($node['name'] ?? '')] = $node;
}

$failed = false;
function aiHubCheck(string $label, bool $passes): void
{
    global $failed;
    echo ($passes ? 'PASS ' : 'FAIL ') . $label . PHP_EOL;
    $failed = $failed || !$passes;
}

aiHubCheck('AI Hub has the requested name', ($workflow['name'] ?? '') === 'PaperTrade AI – Telegram Hub');
aiHubCheck('AI Hub contains 28 nodes', count($nodes) === 28);
aiHubCheck(
    'Question branch uses AI Agent v3.1',
    ($byName['AI Stock Chatbot']['type'] ?? '') === '@n8n/n8n-nodes-langchain.agent'
    && ($byName['AI Stock Chatbot']['typeVersion'] ?? null) === 3.1
);
aiHubCheck(
    'Agent prompt receives the raw question and factual context',
    str_contains((string) ($byName['AI Stock Chatbot']['parameters']['text'] ?? ''), '$json.question')
    && str_contains((string) ($byName['AI Stock Chatbot']['parameters']['text'] ?? ''), '$json.factual_answer')
);
aiHubCheck(
    'OpenRouter model uses an encrypted credential reference',
    ($byName['OpenRouter Chat Model']['type'] ?? '') === '@n8n/n8n-nodes-langchain.lmChatOpenRouter'
    && ($byName['OpenRouter Chat Model']['credentials']['openRouterApi']['name'] ?? '') === 'PaperTrade OpenRouter'
);
aiHubCheck(
    'Memory is isolated by Telegram chat ID',
    ($byName['Telegram Chat Memory']['type'] ?? '') === '@n8n/n8n-nodes-langchain.memoryBufferWindow'
    && str_contains((string) ($byName['Telegram Chat Memory']['parameters']['sessionKey'] ?? ''), 'chat_id')
);
aiHubCheck(
    'AI model and memory connect to the Agent',
    ($workflow['connections']['OpenRouter Chat Model']['ai_languageModel'][0][0]['node'] ?? '') === 'AI Stock Chatbot'
    && ($workflow['connections']['Telegram Chat Memory']['ai_memory'][0][0]['node'] ?? '') === 'AI Stock Chatbot'
);
aiHubCheck(
    'Agent output connects to Telegram',
    ($workflow['connections']['AI Stock Chatbot']['main'][0][0]['node'] ?? '') === 'Hub send stock answer'
    && ($byName['Hub send stock answer']['parameters']['text'] ?? '') === '={{$json.output}}'
);

$telegramCredentials = [];
foreach ($nodes as $node) {
    if (($node['type'] ?? '') !== 'n8n-nodes-base.telegram') continue;
    $credential = $node['credentials']['telegramApi'] ?? [];
    $telegramCredentials[($credential['id'] ?? '') . ':' . ($credential['name'] ?? '')] = true;
}
aiHubCheck('All four Telegram nodes share one credential', count($telegramCredentials) === 1);

$questionService = (string) file_get_contents($root . '/app/Services/TelegramQuestionService.php');
aiHubCheck(
    'WAMP poll payload separates question and authoritative facts',
    str_contains($questionService, "\$answer['question']")
    && str_contains($questionService, "\$answer['factual_answer']")
    && str_contains($questionService, "\$answer['has_stock_context']")
);

exit($failed ? 1 : 0);
