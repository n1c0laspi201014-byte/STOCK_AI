<?php
declare(strict_types=1);

namespace App\Support;

use Throwable;

final class Router
{
    private array $routes = [];

    public function add(string $method, string $path, callable|array $handler, array $middleware = []): self
    {
        $this->routes[] = [strtoupper($method), '/' . trim($path, '/'), $handler, $middleware];
        return $this;
    }

    public function get(string $path, callable|array $handler, array $middleware = []): self
    {
        return $this->add('GET', $path, $handler, $middleware);
    }

    public function post(string $path, callable|array $handler, array $middleware = []): self
    {
        return $this->add('POST', $path, $handler, $middleware);
    }

    public function patch(string $path, callable|array $handler, array $middleware = []): self
    {
        return $this->add('PATCH', $path, $handler, $middleware);
    }

    public function delete(string $path, callable|array $handler, array $middleware = []): self
    {
        return $this->add('DELETE', $path, $handler, $middleware);
    }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes as [$method, $path, $handler, $middleware]) {
            if ($method !== $request->method()) {
                continue;
            }
            $paramNames = [];
            $quoted = preg_quote($path, '#');
            $pattern = preg_replace_callback('/\\\\\{([A-Za-z_][A-Za-z0-9_]*)\\\\\}/', static function (array $match) use (&$paramNames): string {
                $paramNames[] = $match[1];
                return '([^/]+)';
            }, $quoted);
            if (!preg_match('#^' . $pattern . '$#', $request->path(), $matches)) {
                continue;
            }
            array_shift($matches);
            $request->setRouteParams(array_combine($paramNames, array_map('urldecode', $matches)) ?: []);
            try {
                foreach ($middleware as $middlewareClass) {
                    (new $middlewareClass())->handle($request);
                }
                $callable = is_array($handler) && is_string($handler[0]) ? [new $handler[0](), $handler[1]] : $handler;
                $result = $callable($request);
                if (is_string($result)) {
                    echo $result;
                }
            } catch (Throwable $exception) {
                Logger::write('error', $exception->getMessage(), ['type' => $exception::class]);
                if ($request->expectsJson()) {
                    Response::json(['success' => false, 'error_code' => 'REQUEST_FAILED', 'message' => config('app.debug') ? $exception->getMessage() : 'The request could not be completed.', 'retryable' => false], 422);
                }
                Response::status(500);
                View::display('errors/500', ['exception' => $exception], 'layouts/auth');
            }
            return;
        }

        Response::status(404);
        if ($request->expectsJson()) {
            Response::json(['success' => false, 'error_code' => 'NOT_FOUND', 'message' => 'Endpoint not found.', 'retryable' => false], 404);
        }
        View::display('errors/404', [], 'layouts/auth');
    }
}

