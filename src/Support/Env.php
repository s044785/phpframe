<?php
declare(strict_types=1);

namespace PHPFrame\Support;

// 环境变量管理：从 .env 文件加载配置，并提供读取方法
final class Env
{
    // 加载 .env 文件，将键值对写入环境变量（getenv / $_ENV）
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

    // 读取环境变量，不存在时返回默认值
    public static function get(string $key, ?string $default = null): ?string
    {
        $v = getenv($key);
        if ($v === false) {
            return $default;
        }
        return (string)$v;
    }

    // 读取必需的环境变量，不存在时抛出异常
    public static function require(string $key): string
    {
        $v = self::get($key);
        if ($v === null || $v === '') {
            throw new \RuntimeException('缺少环境变量: ' . $key);
        }
        return $v;
    }

    // 去除值的双引号或单引号包裹
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
