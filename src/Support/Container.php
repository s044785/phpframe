<?php
declare(strict_types=1);

namespace PHPFrame\Support;

final class Container
{
    /** @var array<string, mixed> */
    private static array $bindings = [];

    public static function set(string $key, mixed $value): void
    {
        self::$bindings[$key] = $value;
    }

    public static function get(string $key): mixed
    {
        if (!array_key_exists($key, self::$bindings)) {
            throw new \RuntimeException('Container key not found: ' . $key);
        }
        return self::$bindings[$key];
    }
}
