<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\PredictionService;
use App\Support\Container;
use App\Support\Request;
use App\Support\View;

final class PredictionsController
{
    public function index(Request $request): string
    {
        $userId = (int) auth_user()['id'];
        $service = Container::get(PredictionService::class);
        $coverage = $service->generateMissingForUser($userId);
        return View::render('predictions/index', [
            'pageTitle' => 'Predictions', 'activePage' => 'predictions', 'pageScript' => 'predictions.js',
            'ownedPredictions' => $service->owned($userId),
            'watchlistPredictions' => $service->watchlisted($userId),
            'opportunities' => $service->discover($userId, true),
            'history' => $service->history($userId),
            'generationSummary' => $coverage,
        ]);
    }
}
