<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap/app.php';

use App\Services\PredictionScoreService;
use App\Services\TechnicalIndicatorService;
use App\Support\Csrf;
use App\Support\Request;
use App\Support\Router;

$failed = false;
function unitCheck(string $label, bool $passes): void { global $failed; echo ($passes ? 'PASS ' : 'FAIL ') . $label . PHP_EOL; $failed = $failed || !$passes; }

$points = [];
for ($i = 0; $i < 60; $i++) $points[] = ['timestamp' => date(DATE_ATOM, strtotime("-{$i} days")), 'close' => 100 + ($i * .5), 'volume' => 1000 + $i];
$technical = (new TechnicalIndicatorService())->calculate($points);
unitCheck('Technical indicators are bounded', $technical['available'] && $technical['score'] >= 5 && $technical['score'] <= 95);
unitCheck('RSI generated', isset($technical['indicators']['rsi']) && $technical['indicators']['rsi'] >= 0 && $technical['indicators']['rsi'] <= 100);

$scores = new PredictionScoreService();
$combined = $scores->combine(70, null, 50);
unitCheck('Missing weights are redistributed', $combined['available'] && in_array('news', $combined['missing'], true));
unitCheck('Probability is clamped and complementary', $combined['probability_up'] >= 5 && $combined['probability_up'] <= 95 && abs($combined['probability_up'] + $combined['probability_down'] - 100) < .001);
unitCheck('Probability and confidence are separate inputs', $scores->signal(72, 40, 'low', false) === 'watch');

$token = Csrf::token();
unitCheck('CSRF accepts current token', Csrf::validate($token));
unitCheck('CSRF rejects wrong token', !Csrf::validate('wrong-token'));

$testRouter = new Router();
$matched = false;
$testRouter->get('/items/{stockId}', static function (Request $request) use (&$matched): void { $matched = $request->route('stockId') === '42'; });
$_SERVER['REQUEST_METHOD'] = 'GET'; $_SERVER['REQUEST_URI'] = '/items/42'; $_SERVER['SCRIPT_NAME'] = '/index.php';
ob_start(); $testRouter->dispatch(new Request()); ob_end_clean();
unitCheck('Router extracts dynamic parameter', $matched);

exit($failed ? 1 : 0);

