<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Config\Env;
use App\Support\Request;
use App\Support\Response;

final class InternalApiMiddleware
{
    public function handle(Request $request): void
    {
        $expected = (string) Env::get('INTERNAL_N8N_API_KEY', '');
        $provided = (string) $request->header('X-Internal-Api-Key', '');
        if ($expected === '' || $expected === 'replace-with-a-long-random-value' || $provided === '' || !hash_equals($expected, $provided)) {
            Response::json(['success' => false, 'error_code' => 'INTERNAL_AUTH_FAILED', 'message' => 'Valid internal API credentials are required.', 'retryable' => false], 401);
        }
    }
}

