<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Services\PredictionService;
use App\Support\Container;
use App\Support\Request;
use App\Support\Response;

final class PredictionApiController
{
    private function service(): PredictionService { return Container::get(PredictionService::class); }
    public function owned(Request $request): never { Response::json(['success' => true, 'data' => $this->service()->owned((int) auth_user()['id'])]); }
    public function watchlist(Request $request): never { Response::json(['success' => true, 'data' => $this->service()->watchlisted((int) auth_user()['id'])]); }
    public function discovery(Request $request): never { Response::json(['success' => true, 'data' => $this->service()->discover((int) auth_user()['id'])]); }
    public function history(Request $request): never { $id = $request->input('stock_id'); Response::json(['success' => true, 'data' => $this->service()->history((int) auth_user()['id'], $id !== null ? (int) $id : null)]); }
    public function generate(Request $request): never { $identifier = $request->input('stock_id') ?: $request->input('symbol'); $prediction = $this->service()->generate((int) auth_user()['id'], is_numeric($identifier) ? (int) $identifier : (string) $identifier, (string) $request->input('horizon', '7d')); Response::json(['success' => true, 'message' => 'Prediction generated as an uncertain educational estimate.', 'data' => $prediction]); }
    public function discover(Request $request): never { Response::json(['success' => true, 'data' => $this->service()->discover((int) auth_user()['id'], true)]); }
}

