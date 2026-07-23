<?php
declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\TransactionRepository;
use App\Repositories\UserRepository;
use App\Services\PortfolioService;
use App\Support\Container;
use App\Support\Request;
use App\Support\Response;
use App\Support\Validator;

final class PortfolioApiController
{
    public function show(Request $request): never { Response::json(['success' => true, 'data' => Container::get(PortfolioService::class)->data((int) auth_user()['id'])]); }
    public function transactions(Request $request): never { Response::json(['success' => true, 'data' => Container::get(TransactionRepository::class)->forUser((int) auth_user()['id'])]); }
    public function buy(Request $request): never { $data = Container::get(PortfolioService::class)->buy((int) auth_user()['id'], (string) $request->input('symbol'), Validator::positiveDecimal($request->input('quantity')), (string) $request->input('exchange', ''), filter_var($request->input('keep_watchlisted', false), FILTER_VALIDATE_BOOL)); Response::json(['success' => true, 'message' => 'Simulated purchase completed. No real order was placed.', 'data' => $data]); }
    public function sell(Request $request): never { $data = Container::get(PortfolioService::class)->sell((int) auth_user()['id'], (string) $request->input('symbol'), Validator::positiveDecimal($request->input('quantity')), (string) $request->input('exchange', '')); Response::json(['success' => true, 'message' => 'Simulated sale completed. No real order was placed.', 'data' => $data]); }
    public function reset(Request $request): never { $userId = (int) auth_user()['id']; if (!Container::get(UserRepository::class)->verifyPassword($userId, (string) $request->input('password'))) Response::json(['success' => false, 'error_code' => 'PASSWORD_INVALID', 'message' => 'Password confirmation failed.', 'retryable' => false], 403); Container::get(PortfolioService::class)->reset($userId); Response::json(['success' => true, 'message' => 'Paper portfolio reset to its starting balance.']); }
}

