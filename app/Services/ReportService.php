<?php
declare(strict_types=1);

namespace App\Services;

use PDO;

final class ReportService
{
    public function __construct(private readonly PDO $pdo, private readonly PortfolioService $portfolio, private readonly PredictionService $predictions) {}

    public function dueMorningReports(?\DateTimeImmutable $clock = null): array
    {
        $rows = $this->pdo->query('SELECT u.id AS user_id,u.name,us.timezone,us.morning_report_time,tc.chat_id FROM users u JOIN user_settings us ON us.user_id=u.id JOIN telegram_connections tc ON tc.user_id=u.id AND tc.is_enabled=1 AND tc.is_verified=1 WHERE us.morning_report_enabled=1')->fetchAll();
        $due = [];
        $instant = $clock ?? new \DateTimeImmutable('now');
        foreach ($rows as $row) {
            $now = $instant->setTimezone(new \DateTimeZone((string) $row['timezone']));
            $reportDate = $now->format('Y-m-d');
            $scheduledMinute = substr((string) $row['morning_report_time'], 0, 5);
            if ($now->format('H:i') < $scheduledMinute || $this->alreadySent('morning-report', (int) $row['user_id'], $reportDate)) continue;
            $row['report_date'] = $reportDate;
            $due[] = $row;
        }
        return $due;
    }

    public function dueCloseReports(?\DateTimeImmutable $clock = null): array
    {
        $rows = $this->pdo->query('SELECT u.id AS user_id,u.name,tc.chat_id FROM users u JOIN user_settings us ON us.user_id=u.id JOIN telegram_connections tc ON tc.user_id=u.id AND tc.is_enabled=1 AND tc.is_verified=1 WHERE us.market_close_report_enabled=1')->fetchAll();
        $now = ($clock ?? new \DateTimeImmutable('now'))->setTimezone(new \DateTimeZone('America/New_York'));
        if ((int) $now->format('N') > 5 || $now->format('H:i') < '16:00') return [];
        $reportDate = $now->format('Y-m-d');
        $due = [];
        foreach ($rows as $row) {
            if ($this->alreadySent('market-close-report', (int) $row['user_id'], $reportDate)) continue;
            $row['report_date'] = $reportDate;
            $due[] = $row;
        }
        return $due;
    }

    public function data(int $userId, string $type = 'morning-report'): array
    {
        $portfolio = $this->portfolio->data($userId); $predictions = array_slice($this->predictions->owned($userId),0,5);
        $summary = $portfolio['summary'];
        $lines = [$type === 'market-close-report' ? '📊 PAPERTRADE AI — MARKET CLOSE' : '☀️ PAPERTRADE AI — MORNING REPORT', '', 'Portfolio value: ' . money((float)$summary['portfolio_value'],(string)$summary['base_currency']), 'Virtual cash: ' . money((float)$summary['current_cash'],(string)$summary['base_currency']), 'Owned stocks: ' . (int)$summary['owned_count'], 'Watchlist stocks: ' . (int)$summary['watchlist_count'], 'Data prepared: ' . date(DATE_ATOM), ''];
        foreach($predictions as $prediction) $lines[] = $prediction['symbol'] . ': ' . strtoupper($prediction['signal']) . ', estimated ' . number_format((float)$prediction['estimated_probability_up'],1) . '% up over ' . $prediction['horizon'] . ', confidence ' . number_format((float)$prediction['confidence_score'],1) . '%';
        $lines[]=''; $lines[]=config('app.disclaimer');
        return ['user_id'=>$userId,'type'=>$type,'message'=>implode("\n",$lines),'portfolio'=>$portfolio,'predictions'=>$predictions];
    }

    private function alreadySent(string $workflow,int $userId,string $date): bool
    {
        $statement=$this->pdo->prepare('SELECT 1 FROM automation_logs WHERE workflow_name=:workflow AND user_id=:user_id AND status="success" AND (execution_key=:execution_key OR (execution_key IS NULL AND DATE(started_at)=:report_date)) LIMIT 1');
        $statement->execute(['workflow' => $workflow, 'user_id' => $userId, 'execution_key' => $date . '-' . $userId, 'report_date' => $date]);
        return (bool)$statement->fetchColumn();
    }
}
