<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Services\MarketDataService;
use App\Support\Container;
use App\Support\Request;
use App\Support\Response;
use App\Support\Validator;

final class MarketApiController
{
    private function service(): MarketDataService { return Container::get(MarketDataService::class); }
    private function send(array $result): never { Response::json($result, ($result['success'] ?? false) ? 200 : 422); }
    public function search(Request $request): never { $this->send($this->service()->search((string) $request->input('q', ''))); }
    public function quote(Request $request): never { $this->send($this->service()->quote(Validator::symbol($request->input('symbol')), (string) $request->input('exchange', ''))); }
    public function history(Request $request): never { $range = Validator::oneOf($request->input('range', '1m'), ['1d','7d','1m','3m','1y'], 'range'); $this->send($this->service()->history(Validator::symbol($request->input('symbol')), $range)); }
    public function profile(Request $request): never { $this->send($this->service()->profile(Validator::symbol($request->input('symbol')))); }
    public function news(Request $request): never { $this->send($this->service()->news(Validator::symbol($request->input('symbol')))); }
    public function status(Request $request): never { $this->send($this->service()->marketStatus((string) $request->input('exchange', 'US'))); }
    public function refresh(Request $request): never { $symbol = $request->input('symbol'); $result = $symbol ? $this->service()->quote(Validator::symbol($symbol), (string) $request->input('exchange', ''), true) : $this->service()->marketStatus('US'); $this->send($result); }
}

