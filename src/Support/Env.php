<?php
declare(strict_types=1);

namespace PHPFrame\Support;

final class Env
{
    public static function load(string $path): void
    {
        if (!is_file($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            return;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $key = trim(substr($line, 0, $pos));
            $val = trim(substr($line, $pos + 1));
            $val = self::unquote($val);
            if ($key !== '' && getenv($key) === false) {
                putenv($key . '=' . $val);
                $_ENV[$key] = $val;
            }
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $v = getenv($key);
        if ($v === false) {
            return $default;
        }
        return (string)$v;
    }

    public static function require(string $key): string
    {
        $v = self::get($key);
        if ($v === null || $v === '') {
            throw new \RuntimeException('Missing env: ' . $key);
        }
        return $v;
    }

    private static function unquote(string $val): string
    {
        if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
            (str_starts_with($val, "'") && str_ends_with($val, "'"))
        ) {
            return substr($val, 1, -1);
        }
        return $val;
    }
}

