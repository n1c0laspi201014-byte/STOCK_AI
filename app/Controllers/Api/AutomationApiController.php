<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\AlertEventRepository;
use App\Services\AlertService;
use App\Services\AutomationService;
use App\Services\ReportService;
use App\Services\TelegramQuestionService;
use App\Support\Container;
use App\Support\Request;
use App\Support\Response;

final class AutomationApiController
{
    private function enabled(): bool { return Container::get(AutomationService::class)->enabled(); }

    public function dueAlertRules(Request $request): never
    {
        Response::json(['success' => true, 'data' => $this->enabled() ? Container::get(AlertService::class)->dueRules() : [], 'automations_paused' => !$this->enabled()]);
    }

    public function evaluateAlert(Request $request): never
    {
        if (!$this->enabled() && !filter_var($request->input('force_test', false), FILTER_VALIDATE_BOOL)) Response::json(['success' => true, 'triggered' => false, 'reason' => 'Automations are paused.']);
        Response::json(['success' => true] + Container::get(AlertService::class)->evaluate((int) $request->input('rule_id'), filter_var($request->input('force_test', false), FILTER_VALIDATE_BOOL)));
    }

    public function dueMorningReports(Request $request): never { Response::json(['success' => true, 'data' => $this->enabled() ? Container::get(ReportService::class)->dueMorningReports() : []]); }
    public function dueCloseReports(Request $request): never { Response::json(['success' => true, 'data' => $this->enabled() ? Container::get(ReportService::class)->dueCloseReports() : []]); }
    public function reportData(Request $request): never { Response::json(['success' => true, 'data' => Container::get(ReportService::class)->data((int) $request->input('user_id'), (string) $request->input('type', 'morning-report'))]); }
    public function log(Request $request): never { $id = Container::get(AutomationService::class)->log($request->all()); Response::json(['success' => true, 'id' => $id]); }

    public function telegramResult(Request $request): never
    {
        Container::get(AlertEventRepository::class)->telegramResult((int) $request->input('event_id'), filter_var($request->input('success', false), FILTER_VALIDATE_BOOL), $request->input('error') ? substr((string) $request->input('error'), 0, 500) : null);
        Response::json(['success' => true]);
    }

    public function telegramStockQuestions(Request $request): never
    {
        Response::json(['success' => true, 'data' => Container::get(TelegramQuestionService::class)->poll()]);
    }

}
