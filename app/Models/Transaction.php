<?php
declare(strict_types=1);
namespace App\Models;
final class Transaction { public function __construct(public readonly int $id, public readonly string $type, public readonly float $quantity, public readonly float $price, public readonly float $fee) {} }

