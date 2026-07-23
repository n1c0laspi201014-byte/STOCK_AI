<?php
declare(strict_types=1);

namespace App\Support;

use InvalidArgumentException;

final class Validator
{
    public static function positiveDecimal(mixed $value, string $field = 'quantity'): float
    {
        if (!is_numeric($value) || !is_finite((float) $value) || (float) $value <= 0) {
            throw new InvalidArgumentException(ucfirst($field) . ' must be greater than zero.');
        }
        return (float) $value;
    }

    public static function symbol(mixed $value): string
    {
        $symbol = strtoupper(trim((string) $value));
        if ($symbol === '' || !preg_match('/^[A-Z0-9.:-]{1,32}$/', $symbol)) {
            throw new InvalidArgumentException('Enter a valid stock symbol.');
        }
        return $symbol;
    }

    public static function oneOf(mixed $value, array $allowed, string $field): string
    {
        $value = (string) $value;
        if (!in_array($value, $allowed, true)) {
            throw new InvalidArgumentException("Invalid {$field}.");
        }
        return $value;
    }
}

