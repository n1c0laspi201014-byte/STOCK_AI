<?php
declare(strict_types=1);

namespace App\Services;

use App\Repositories\StockRepository;
use App\Repositories\WatchlistRepository;
use InvalidArgumentException;

final class WatchlistService
{
    public function __construct(private readonly WatchlistRepository $watchlist, private readonly StockRepository $stocks, private readonly MarketDataService $market) {}

    public function all(int $userId): array { return $this->watchlist->all($userId); }

    public function add(int $userId, array $data): array
    {
        $stock = null;
        if (!empty($data['stock_id'])) $stock = $this->stocks->findById((int) $data['stock_id']);
        if ($stock === null && !empty($data['symbol'])) {
            $symbol = strtoupper(trim((string) $data['symbol']));
            $profile = $this->market->profile($symbol);
            $stock = $profile['success'] ? $this->stocks->upsert($profile['data']) : $this->stocks->findBySymbol($symbol);
        }
        if ($stock === null) throw new InvalidArgumentException('Stock not found. Search for it first.');
        $created = $this->watchlist->add($userId, (int) $stock['id']);
        return ['created' => $created, 'message' => $created ? 'Added to watchlist.' : 'Already in watchlist.', 'stock' => $stock];
    }
}

