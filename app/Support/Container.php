<?php
declare(strict_types=1);

namespace App\Support;

use App\Config\Database;
use PDO;
use ReflectionClass;
use RuntimeException;

final class Container
{
    private static array $instances = [];

    public static function get(string $class): object
    {
        if ($class === PDO::class) return Database::connection();
        if (isset(self::$instances[$class])) return self::$instances[$class];
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();
        if ($constructor === null) return self::$instances[$class] = $reflection->newInstance();
        $arguments = [];
        foreach ($constructor->getParameters() as $parameter) {
            $type = $parameter->getType();
            if ($type === null || $type->isBuiltin()) throw new RuntimeException("Cannot resolve {$class}::{$parameter->getName()}");
            $arguments[] = self::get($type->getName());
        }
        return self::$instances[$class] = $reflection->newInstanceArgs($arguments);
    }
}

