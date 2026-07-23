<?php
declare(strict_types=1);
namespace App\Models;
final class AlertRule { public function __construct(public readonly int $id, public readonly int $stockId, public readonly string $name, public readonly string $thresholdType, public readonly float $thresholdValue) {} }

