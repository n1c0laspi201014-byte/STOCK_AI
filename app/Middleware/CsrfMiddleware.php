<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Support\Csrf;
use App\Support\Request;
use App\Support\Response;

final class CsrfMiddleware
{
    public function handle(Request $request): void
    {
        $token = (string) ($request->header('X-CSRF-TOKEN') ?? $request->input('_csrf', ''));
        if (Csrf::validate($token)) {
            return;
        }
        if ($request->expectsJson()) {
            Response::json(['success' => false, 'error_code' => 'CSRF_INVALID', 'message' => 'Your session token expired. Refresh the page and try again.', 'retryable' => true], 419);
        }
        $_SESSION['flash_error'] = 'Your session token expired. Please try again.';
        Response::redirect($_SERVER['HTTP_REFERER'] ?? url('/login'));
    }
}

