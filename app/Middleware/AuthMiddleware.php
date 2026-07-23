<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Support\Request;
use App\Support\Response;

final class AuthMiddleware
{
    public function handle(Request $request): void
    {
        if (auth_user() !== null) {
            return;
        }
        if ($request->expectsJson()) {
            Response::json(['success' => false, 'error_code' => 'AUTH_REQUIRED', 'message' => 'Please log in.', 'retryable' => false], 401);
        }
        $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? url('/dashboard');
        Response::redirect(url('/login'));
    }
}

