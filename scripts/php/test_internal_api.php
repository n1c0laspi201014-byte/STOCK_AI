<?php
declare(strict_types=1);

use App\Config\Env;

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';
$base = rtrim((string) config('app.url'), '/');
$key = (string) Env::get('INTERNAL_N8N_API_KEY', '');
if ($key === '' || $key === 'replace-with-a-long-random-value') {
    echo "BLOCKED_BY_SETUP Internal API key\n  Tested: INTERNAL_N8N_API_KEY is a non-placeholder value\n  Likely fix: generate a long random value, set it in .env and docker-compose environment, then restart Apache/n8n.\n";
    exit(2);
}

function status(string $url, ?string $key): int {
    $curl = curl_init($url); $headers = ['Accept: application/json']; if ($key !== null) $headers[] = 'X-Internal-Api-Key: ' . $key;
    curl_setopt_array($curl, [CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>8,CURLOPT_HTTPHEADER=>$headers]); curl_exec($curl); $status=(int)curl_getinfo($curl,CURLINFO_RESPONSE_CODE); curl_close($curl); return $status;
}
$url = $base . '/api/internal/automation/due-alert-rules';
$missing = status($url, null); $wrong = status($url, 'wrong-key'); $correct = status($url, $key);
foreach ([['Missing key rejected', in_array($missing,[401,403],true)],['Wrong key rejected',in_array($wrong,[401,403],true)],['Correct key accepted',$correct===200]] as [$label,$pass]) echo ($pass?'PASS ':'FAIL ').$label.PHP_EOL;
if ($correct === 0) echo "  Likely fix: start WAMP and open {$base}/health.php before rerunning.\n";
exit($missing>=400 && $wrong>=400 && $correct===200 ? 0 : 1);

