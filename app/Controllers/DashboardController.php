<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Services\DashboardService;
use App\Support\Container;
use App\Support\Request;
use App\Support\View;

final class DashboardController
{
    public function index(Request $request): string
    {
        $data = Container::get(DashboardService::class)->data((int) auth_user()['id']);
        return View::render('dashboard/index', $data + ['pageTitle' => 'Dashboard', 'activePage' => 'dashboard', 'pageScript' => 'dashboard.js']);
    }
}

