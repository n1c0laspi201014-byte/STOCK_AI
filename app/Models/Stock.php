<?php
declare(strict_types=1);
namespace App\Models;
final class Stock { public function __construct(public readonly int $id, public readonly string $symbol, public readonly string $companyName, public readonly string $exchange, public readonly string $currency) {} }

