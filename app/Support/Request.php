<?php
declare(strict_types=1);

namespace App\Support;

final class Request
{
    private ?array $json = null;
    private array $routeParams = [];

    public function method(): string
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper((string) $_POST['_method']);
        }
        return $method;
    }

    public function path(): string
    {
        $path = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');
        $scriptDirectory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
        if ($scriptDirectory !== '/' && $scriptDirectory !== '.' && str_starts_with($path, $scriptDirectory)) {
            $path = substr($path, strlen($scriptDirectory));
        }
        $path = '/' . trim($path, '/');
        return $path === '//' ? '/' : $path;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $data = array_merge($_GET, $_POST, $this->json());
        return $data[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($_GET, $_POST, $this->json());
    }

    public function header(string $name, ?string $default = null): ?string
    {
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return isset($_SERVER[$serverKey]) ? trim((string) $_SERVER[$serverKey]) : $default;
    }

    public function expectsJson(): bool
    {
        return str_contains((string) $this->header('Accept', ''), 'application/json') || str_starts_with($this->path(), '/api/');
    }

    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    public function route(string $key, mixed $default = null): mixed
    {
        return $this->routeParams[$key] ?? $default;
    }

    private function json(): array
    {
        if ($this->json !== null) {
            return $this->json;
        }
        if (!str_contains((string) ($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json')) {
            return $this->json = [];
        }
        $decoded = json_decode((string) file_get_contents('php://input'), true);
        return $this->json = is_array($decoded) ? $decoded : [];
    }
}

