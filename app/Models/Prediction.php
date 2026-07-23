<?php
declare(strict_types=1);
namespace App\Models;
final class Prediction { public function __construct(public readonly int $id, public readonly string $signal, public readonly float $estimatedProbabilityUp, public readonly float $confidence, public readonly string $horizon, public readonly string $risk) {} }

