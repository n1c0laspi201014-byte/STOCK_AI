<?php
declare(strict_types=1);
namespace App\Models;
final class Holding { public function __construct(public readonly int $stockId, public readonly float $quantity, public readonly float $averageCost, public readonly float $totalCost, public readonly float $realizedProfitLoss) {} }

