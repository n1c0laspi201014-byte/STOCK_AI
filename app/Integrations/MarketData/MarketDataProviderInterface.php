<?php
declare(strict_types=1);

namespace App\Integrations\MarketData;

interface MarketDataProviderInterface
{
    public function name(): string;
    public function isConfigured(): bool;
    public function search(string $query): array;
    public function quote(string $symbol, string $exchange = ''): array;
    public function profile(string $symbol): array;
    public function history(string $symbol, string $range): array;
    public function news(string $symbol, int $limit = 5): array;
    public function marketStatus(string $exchange = 'US'): array;
}

