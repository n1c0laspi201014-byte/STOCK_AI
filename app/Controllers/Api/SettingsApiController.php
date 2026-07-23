<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Integrations\Telegram\TelegramTestClient;
use App\Repositories\AlertRuleRepository;
use App\Repositories\AlertEventRepository;
use App\Repositories\AutomationLogRepository;
use App\Repositories\SettingsRepository;
use App\Services\AlertService;
use App\Services\AutomationService;
use App\Support\Container;
use App\Support\Request;
use App\Support\Response;
use App\Support\Validator;
use InvalidArgumentException;

final class SettingsApiController
{
    public function index(Request $request): never
    {
        Response::json(['success' => true, 'data' => Container::get(SettingsRepository::class)->get((int) auth_user()['id'])]);
    }

    public function profile(Request $request): never
    {
        $name = trim((string) $request->input('name'));
        $timezone = (string) $request->input('timezone', config('app.timezone'));
        if ($name === '' || strlen($name) > 100 || !in_array($timezone, timezone_identifiers_list(), true)) throw new InvalidArgumentException('Enter a valid name and timezone.');
        Container::get(SettingsRepository::class)->updateProfile((int) auth_user()['id'], compact('name', 'timezone'));
        $_SESSION['user']['name'] = $name;
        $this->saved();
    }

    public function dashboard(Request $request): never
    {
        Container::get(SettingsRepository::class)->updateDashboard((int) auth_user()['id'], $request->all());
        $this->saved();
    }

    public function paperAccount(Request $request): never
    {
        Container::get(SettingsRepository::class)->updatePaperAccount((int) auth_user()['id'], $request->all());
        $this->saved();
    }

    public function ai(Request $request): never
    {
        Validator::oneOf($request->input('default_horizon', '7d'), ['1d', '7d', '30d'], 'horizon');
        Container::get(SettingsRepository::class)->updateAi((int) auth_user()['id'], $request->all());
        $this->saved();
    }

    public function telegramVerify(Request $request): never
    {
        $chatId = trim((string) $request->input('chat_id'));
        if ($chatId === '' || strlen($chatId) > 100) throw new InvalidArgumentException('Enter a valid Telegram chat ID.');
        Container::get(SettingsRepository::class)->saveTelegram((int) auth_user()['id'], $chatId, $request->input('telegram_username') ? trim((string) $request->input('telegram_username')) : null, filter_var($request->input('is_enabled', true), FILTER_VALIDATE_BOOL));
        Response::json(['success' => true, 'message' => 'Telegram connection details saved. Send a test message to verify them.']);
    }

    public function telegramTest(Request $request): never
    {
        $userId = (int) auth_user()['id'];
        $repository = Container::get(SettingsRepository::class);
        $settings = $repository->get($userId);
        $chatId = trim((string) ($request->input('chat_id') ?: ($settings['chat_id'] ?? '')));
        if ($chatId === '') throw new InvalidArgumentException('Save a Telegram chat ID first.');
        $repository->saveTelegram($userId, $chatId, $request->input('telegram_username') ?: ($settings['telegram_username'] ?? null), true);
        $result = Container::get(TelegramTestClient::class)->send($chatId, "STOCK AI connection successful.\n\n" . config('app.disclaimer'));
        $repository->markTelegramTest($userId, (bool) ($result['success'] ?? false));
        Response::json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    public function alerts(Request $request): never
    {
        Response::json(['success' => true, 'data' => Container::get(AlertRuleRepository::class)->all((int) auth_user()['id'])]);
    }

    public function storeAlert(Request $request): never
    {
        $data = $this->alertData($request);
        $id = Container::get(AlertRuleRepository::class)->save((int) auth_user()['id'], $data);
        Response::json(['success' => true, 'message' => 'Alert rule created.', 'id' => $id]);
    }

    public function updateAlert(Request $request): never
    {
        $id = (int) $request->route('id');
        if (Container::get(AlertRuleRepository::class)->findOwned((int) auth_user()['id'], $id) === null) Response::json(['success' => false, 'error_code' => 'NOT_FOUND', 'message' => 'Alert rule not found.', 'retryable' => false], 404);
        Container::get(AlertRuleRepository::class)->save((int) auth_user()['id'], $this->alertData($request), $id);
        Response::json(['success' => true, 'message' => 'Alert rule updated.']);
    }

    public function deleteAlert(Request $request): never
    {
        $removed = Container::get(AlertRuleRepository::class)->delete((int) auth_user()['id'], (int) $request->route('id'));
        Response::json(['success' => true, 'removed' => $removed]);
    }

    public function testAlert(Request $request): never
    {
        $id = (int) $request->route('id');
        if (Container::get(AlertRuleRepository::class)->findOwned((int) auth_user()['id'], $id) === null) Response::json(['success' => false, 'error_code' => 'NOT_FOUND', 'message' => 'Alert rule not found.', 'retryable' => false], 404);
        $evaluation = Container::get(AlertService::class)->evaluate($id, true);
        if (empty($evaluation['triggered'])) Response::json(['success' => false, 'error_code' => 'ALERT_TEST_FAILED', 'message' => $evaluation['reason'] ?? 'Test alert could not be prepared.', 'retryable' => true], 422);
        $settings = Container::get(SettingsRepository::class)->get((int) auth_user()['id']);
        $result = Container::get(TelegramTestClient::class)->send((string) ($settings['chat_id'] ?? ''), (string) $evaluation['message']);
        Container::get(AlertEventRepository::class)->telegramResult((int) $evaluation['event_id'], (bool) ($result['success'] ?? false), ($result['success'] ?? false) ? null : (string) ($result['message'] ?? 'Telegram send failed.'));
        Response::json($result + ['evaluation' => $evaluation], ($result['success'] ?? false) ? 200 : 422);
    }

    public function logs(Request $request): never
    {
        $this->adminOnly();
        Response::json(['success' => true, 'data' => Container::get(AutomationLogRepository::class)->recent(100)]);
    }

    public function pause(Request $request): never { $this->adminOnly(); Container::get(AutomationService::class)->pause(); Response::json(['success' => true, 'message' => 'Automations paused.']); }
    public function resume(Request $request): never { $this->adminOnly(); Container::get(AutomationService::class)->resume(); Response::json(['success' => true, 'message' => 'Automations resumed.']); }

    private function alertData(Request $request): array
    {
        return [
            'stock_id' => (int) $request->input('stock_id'),
            'name' => trim((string) $request->input('name')) ?: 'Price movement alert',
            'is_enabled' => filter_var($request->input('is_enabled', true), FILTER_VALIDATE_BOOL),
            'threshold_type' => Validator::oneOf($request->input('threshold_type', 'percent'), ['percent', 'absolute_price', 'target_price'], 'threshold type'),
            'threshold_value' => Validator::positiveDecimal($request->input('threshold_value'), 'threshold'),
            'direction' => Validator::oneOf($request->input('direction', 'both'), ['increase', 'decrease', 'both'], 'direction'),
            'reference_type' => Validator::oneOf($request->input('reference_type', 'previous_close'), ['previous_close', 'last_alert_price', 'average_cost', 'fixed_price'], 'reference'),
            'reference_price' => $request->input('reference_price') !== '' ? $request->input('reference_price') : null,
            'check_interval_minutes' => Validator::oneOf((string) $request->input('check_interval_minutes', '5'), ['5', '15', '30', '60'], 'check interval'),
            'cooldown_minutes' => max(1, min(1440, (int) $request->input('cooldown_minutes', 30))),
            'market_hours_only' => filter_var($request->input('market_hours_only', true), FILTER_VALIDATE_BOOL),
            // Every delivered price alert includes the freshest available prediction.
            'ai_commentary_enabled' => true,
            'minimum_confidence' => max(0, min(100, (float) $request->input('minimum_confidence', 0))),
        ];
    }

    private function saved(): never { Response::json(['success' => true, 'message' => 'Settings saved.']); }
    private function adminOnly(): void { if (!is_admin()) Response::json(['success' => false, 'error_code' => 'FORBIDDEN', 'message' => 'Admin role required.', 'retryable' => false], 403); }
}
