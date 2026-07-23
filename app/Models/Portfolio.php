<?php
declare(strict_types=1);
namespace App\Models;
final class Portfolio { public function __construct(public readonly int $id, public readonly int $userId, public readonly string $name, public readonly string $baseCurrency) {} }

