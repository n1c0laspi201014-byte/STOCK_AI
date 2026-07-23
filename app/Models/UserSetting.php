<?php
declare(strict_types=1);
namespace App\Models;
final class UserSetting { public function __construct(public readonly int $userId, public readonly string $baseCurrency, public readonly string $timezone, public readonly float $currentCash) {} }

