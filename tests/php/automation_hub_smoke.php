<?php
declare(strict_types=1);

use App\Config\Database;
use App\Integrations\Telegram\TelegramTestClient;
use App\Services\AlertService;
use App\Services\AutomationService;
use App\Services\MarketDataService;
use App\Services\ReportService;
use App\Services\TelegramQuestionService;
use App\Support\Container;

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';

$failed = false;
function hubCheck(string $label, bool $passes): void
{
    global $failed;
    echo ($passes ? 'PASS ' : 'FAIL ') . $label . PHP_EOL;
    $failed = $failed || !$passes;
}

$root = dirname(__DIR__, 2);
$workflowPath = $root . '/n8n/workflows/papertrade-telegram-hub.json';
$workflow = json_decode((string) file_get_contents($workflowPath), true, 512, JSON_THROW_ON_ERROR);
$nodes = $workflow['nodes'] ?? [];
$telegramNodes = array_values(array_filter($nodes, static fn(array $node): bool => ($node['type'] ?? '') === 'n8n-nodes-base.telegram'));
$agentNode = current(array_filter($nodes, static fn(array $node): bool => ($node['name'] ?? '') === 'AI Stock Chatbot')) ?: [];
$modelNode = current(array_filter($nodes, static fn(array $node): bool => ($node['name'] ?? '') === 'OpenRouter Chat Model')) ?: [];
$memoryNode = current(array_filter($nodes, static fn(array $node): bool => ($node['name'] ?? '') === 'Telegram Chat Memory')) ?: [];
$questionTelegramNode = current(array_filter($nodes, static fn(array $node): bool => ($node['name'] ?? '') === 'Hub send stock answer')) ?: [];
$credentialRefs = [];
foreach ($telegramNodes as $node) {
    $credential = $node['credentials']['telegramApi'] ?? [];
    $credentialRefs[($credential['id'] ?? '') . ':' . ($credential['name'] ?? '')] = true;
}
$schedules = array_values(array_filter($nodes, static fn(array $node): bool => ($node['type'] ?? '') === 'n8n-nodes-base.scheduleTrigger'));
$serialized = json_encode($workflow, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

hubCheck('Hub has the exact requested name', ($workflow['name'] ?? '') === 'PaperTrade AI – Telegram Hub');
hubCheck('Hub contains the 28-node AI version', count($nodes) === 28);
hubCheck('Hub has four schedule branches', count($schedules) === 4);
hubCheck('Hub uses one Telegram credential', count($telegramNodes) === 4 && count($credentialRefs) === 1);
hubCheck(
    'Question branch uses an n8n AI Agent',
    ($agentNode['type'] ?? '') === '@n8n/n8n-nodes-langchain.agent'
    && ($agentNode['typeVersion'] ?? null) === 3.1
    && str_contains((string) ($agentNode['parameters']['text'] ?? ''), '$json.question')
    && str_contains((string) ($agentNode['parameters']['text'] ?? ''), '$json.factual_answer')
);
hubCheck(
    'AI Agent uses the encrypted OpenRouter credential',
    ($modelNode['type'] ?? '') === '@n8n/n8n-nodes-langchain.lmChatOpenRouter'
    && ($modelNode['credentials']['openRouterApi']['name'] ?? '') === 'PaperTrade OpenRouter'
);
hubCheck(
    'AI Agent has per-Telegram-chat memory',
    ($memoryNode['type'] ?? '') === '@n8n/n8n-nodes-langchain.memoryBufferWindow'
    && str_contains((string) ($memoryNode['parameters']['sessionKey'] ?? ''), 'chat_id')
);
hubCheck('Telegram sends the AI output', ($questionTelegramNode['parameters']['text'] ?? '') === '={{$json.output}}');
hubCheck('Alert branch is five-minute configured-rule polling', str_contains($serialized, '"minutesInterval":5') && str_contains($serialized, '/due-alert-rules') && str_contains($serialized, '/evaluate-alert'));
hubCheck('Morning branch checks each minute', str_contains($serialized, '/due-morning-reports') && str_contains($serialized, "'morning-report'"));
hubCheck('Close branch is 4 PM weekdays in New York', str_contains($serialized, '"expression":"0 16 * * 1-5"') && ($workflow['settings']['timezone'] ?? '') === 'America/New_York');
hubCheck('Question branch uses local polling', str_contains($serialized, '/telegram-stock-questions') && !str_contains($serialized, 'telegramTrigger'));
$questionServiceSource = (string) file_get_contents($root . '/app/Services/TelegramQuestionService.php');
hubCheck(
    'Polling supplies raw questions and verified factual context to AI',
    str_contains($questionServiceSource, "\$answer['question']")
    && str_contains($questionServiceSource, "\$answer['factual_answer']")
);

$pdo = Database::connection();
$admin = $pdo->query("SELECT u.id,tc.chat_id FROM users u JOIN telegram_connections tc ON tc.user_id=u.id AND tc.is_enabled=1 AND tc.is_verified=1 WHERE u.email='admin@papertrade.local' LIMIT 1")->fetch();
$stock = $pdo->query("SELECT id,symbol FROM stocks WHERE symbol='AAPL' ORDER BY id LIMIT 1")->fetch();
if (!$admin || !$stock) {
    hubCheck('Verified Admin Telegram connection and AAPL exist', false);
    exit(1);
}

$market = Container::get(MarketDataService::class);
$quoteResult = $market->quote('AAPL', '', true);
hubCheck('Real AAPL quote is available', !empty($quoteResult['success']) && (float) ($quoteResult['data']['price'] ?? 0) > 0);
if (empty($quoteResult['success'])) exit(1);
$current = (float) $quoteResult['data']['price'];

$pdo->beginTransaction();
try {
    $adminId = (int) $admin['id'];
    $stockId = (int) $stock['id'];
    $insert = $pdo->prepare(
        'INSERT INTO alert_rules (user_id,stock_id,name,is_enabled,threshold_type,threshold_value,direction,reference_type,reference_price,check_interval_minutes,cooldown_minutes,market_hours_only,minimum_confidence)
         VALUES (:user_id,:stock_id,:name,1,:threshold_type,:threshold_value,:direction,"fixed_price",:reference_price,5,0,0,0)'
    );
    $insert->execute([
        'user_id' => $adminId,
        'stock_id' => $stockId,
        'name' => 'Hub non-trigger prediction guard',
        'threshold_type' => 'target_price',
        'threshold_value' => round($current * 2, 8),
        'direction' => 'increase',
        'reference_price' => $current,
    ]);
    $guardRuleId = (int) $pdo->lastInsertId();
    $insert->execute([
        'user_id' => $adminId,
        'stock_id' => $stockId,
        'name' => 'Hub triggered prediction test',
        'threshold_type' => 'percent',
        'threshold_value' => 1,
        'direction' => 'increase',
        'reference_price' => round($current / 1.02, 8),
    ]);
    $triggerRuleId = (int) $pdo->lastInsertId();

    $alertService = Container::get(AlertService::class);
    $dueIds = array_map('intval', array_column($alertService->dueRules(), 'id'));
    hubCheck('Due alerts come from configured user rules', in_array($guardRuleId, $dueIds, true) && in_array($triggerRuleId, $dueIds, true));

    $predictionCount = (int) $pdo->query("SELECT COUNT(*) FROM predictions WHERE user_id={$adminId} AND stock_id={$stockId}")->fetchColumn();
    $guard = $alertService->evaluate($guardRuleId);
    $afterGuard = (int) $pdo->query("SELECT COUNT(*) FROM predictions WHERE user_id={$adminId} AND stock_id={$stockId}")->fetchColumn();
    hubCheck('No prediction is generated before a threshold trigger', empty($guard['triggered']) && $afterGuard === $predictionCount);

    $triggered = $alertService->evaluate($triggerRuleId);
    $afterTrigger = (int) $pdo->query("SELECT COUNT(*) FROM predictions WHERE user_id={$adminId} AND stock_id={$stockId}")->fetchColumn();
    hubCheck('Triggered alert creates a fresh prediction', !empty($triggered['triggered']) && $afterTrigger > $afterGuard && str_contains((string) ($triggered['message'] ?? ''), 'AI signal:'));

    $reports = Container::get(ReportService::class);
    $automation = Container::get(AutomationService::class);
    $pdo->prepare('UPDATE user_settings SET morning_report_enabled=1,morning_report_time="10:30:00",market_close_report_enabled=1 WHERE user_id=:user_id')->execute(['user_id' => $adminId]);
    $pdo->prepare("DELETE FROM automation_logs WHERE user_id=:user_id AND workflow_name IN ('morning-report','market-close-report') AND execution_key IN (:morning_key,:close_key)")->execute([
        'user_id' => $adminId,
        'morning_key' => '2026-07-23-' . $adminId,
        'close_key' => '2026-07-23-' . $adminId,
    ]);

    $morningClock = new DateTimeImmutable('2026-07-23T08:30:00+00:00');
    $morningDue = array_map('intval', array_column($reports->dueMorningReports($morningClock), 'user_id'));
    hubCheck('Morning report is due at the chosen local time', in_array($adminId, $morningDue, true));
    $automation->log(['workflow_name' => 'morning-report', 'user_id' => $adminId, 'status' => 'success', 'execution_key' => '2026-07-23-' . $adminId, 'message' => 'Hub test']);
    $morningAgain = array_map('intval', array_column($reports->dueMorningReports($morningClock), 'user_id'));
    hubCheck('Morning report is limited to once per local day', !in_array($adminId, $morningAgain, true));

    $closeClock = new DateTimeImmutable('2026-07-23T20:00:00+00:00');
    $closeDue = array_map('intval', array_column($reports->dueCloseReports($closeClock), 'user_id'));
    hubCheck('Market-close report is due at 4 PM New York on a weekday', in_array($adminId, $closeDue, true));
    $automation->log(['workflow_name' => 'market-close-report', 'user_id' => $adminId, 'status' => 'success', 'execution_key' => '2026-07-23-' . $adminId, 'message' => 'Hub test']);
    $closeAgain = array_map('intval', array_column($reports->dueCloseReports($closeClock), 'user_id'));
    hubCheck('Market-close report is limited to once per market day', !in_array($adminId, $closeAgain, true));
    $weekendDue = $reports->dueCloseReports(new DateTimeImmutable('2026-07-25T20:00:00+00:00'));
    hubCheck('Market-close reports do not run on weekends', $weekendDue === []);

    $morningData = $reports->data($adminId, 'morning-report');
    $closeData = $reports->data($adminId, 'market-close-report');
    hubCheck('Both report messages build successfully', str_contains((string) $morningData['message'], 'MORNING REPORT') && str_contains((string) $closeData['message'], 'MARKET CLOSE'));

    $answer = Container::get(TelegramQuestionService::class)->answer((string) $admin['chat_id'], '/stock AAPL');
    $answerText = (string) ($answer['message'] ?? '');
    hubCheck('Stock answer has real quote and provider timestamp', ($answer['symbol'] ?? '') === 'AAPL' && str_contains($answerText, 'Current price:') && str_contains($answerText, 'Provider:') && str_contains($answerText, 'Updated:'));
    hubCheck('Stock answer includes a prediction', str_contains($answerText, 'Estimate:') && str_contains($answerText, 'Confidence:'));

    $delivery = Container::get(TelegramTestClient::class)->send((string) $admin['chat_id'], "TELEGRAM HUB PRE-CUTOVER TEST\n\n" . $answerText);
    hubCheck('Real pre-cutover Telegram stock answer delivered', !empty($delivery['success']) && !empty($delivery['message_id']));
    if (!empty($delivery['message_id'])) echo 'INFO Telegram question test message ID ' . (int) $delivery['message_id'] . PHP_EOL;
} finally {
    if ($pdo->inTransaction()) $pdo->rollBack();
}

exit($failed ? 1 : 0);
