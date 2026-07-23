<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\Env;
use App\Repositories\AutomationLogRepository;
use PDO;

final class AutomationService
{
    public function __construct(private readonly PDO $pdo, private readonly AutomationLogRepository $logs) {}
    public function enabled(): bool { return Env::bool('AUTOMATIONS_ENABLED', true) && !$this->paused(); }
    public function paused(): bool { $value=$this->pdo->query('SELECT setting_value FROM system_settings WHERE setting_key="automations_paused"')->fetchColumn(); return $value==='1'; }
    public function pause(): void { $this->pdo->exec('INSERT INTO system_settings (setting_key,setting_value) VALUES ("automations_paused","1") ON DUPLICATE KEY UPDATE setting_value="1"'); }
    public function resume(): void { $this->pdo->exec('INSERT INTO system_settings (setting_key,setting_value) VALUES ("automations_paused","0") ON DUPLICATE KEY UPDATE setting_value="0"'); }
    public function log(array $data): int { return $this->logs->add((string)($data['workflow_name']??'unknown'),(string)($data['status']??'partial'),isset($data['user_id'])?(int)$data['user_id']:null,isset($data['message'])?(string)$data['message']:null,is_array($data['context']??null)?$data['context']:[],isset($data['execution_key'])?(string)$data['execution_key']:null); }
}

