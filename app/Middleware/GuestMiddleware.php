<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Support\Request;
use App\Support\Response;

final class GuestMiddleware
{
    public function handle(Request $request): void
    {
        if (auth_user() !== null) {
            Response::redirect(url('/dashboard'));
        }
    }
}

