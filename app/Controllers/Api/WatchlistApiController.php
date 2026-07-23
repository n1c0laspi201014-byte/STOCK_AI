<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\WatchlistRepository;
use App\Services\WatchlistService;
use App\Support\Container;
use App\Support\Request;
use App\Support\Response;

final class WatchlistApiController
{
    public function index(Request $request): never { Response::json(['success' => true, 'data' => Container::get(WatchlistService::class)->all((int) auth_user()['id'])]); }
    public function store(Request $request): never { Response::json(['success' => true, 'data' => Container::get(WatchlistService::class)->add((int) auth_user()['id'], $request->all())]); }
    public function destroy(Request $request): never { $removed = Container::get(WatchlistRepository::class)->remove((int) auth_user()['id'], (int) $request->route('stockId')); Response::json(['success' => true, 'removed' => $removed, 'message' => $removed ? 'Removed from watchlist.' : 'Stock was not in the watchlist.']); }
    public function update(Request $request): never { $target = $request->input('target_buy_price'); $updated = Container::get(WatchlistRepository::class)->update((int) auth_user()['id'], (int) $request->route('stockId'), $request->input('note') !== null ? trim((string) $request->input('note')) : null, $target !== null && $target !== '' ? (float) $target : null); Response::json(['success' => true, 'updated' => $updated]); }
}

