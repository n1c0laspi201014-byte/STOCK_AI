<?php
declare(strict_types=1);
namespace App\Models;
final class WatchlistItem { public function __construct(public readonly int $id, public readonly int $userId, public readonly int $stockId, public readonly ?string $note) {} }

