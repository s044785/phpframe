<?php
declare(strict_types=1);

namespace PHPFrame\Support;

// 简易依赖容器：全局静态绑定，用于注册和获取服务实例
final class Container
{
    /** @var array<string, mixed> */
    private static array $bindings = [];

    // 注册一个绑定
    public static function set(string $key, mixed $value): void
    {
        self::$bindings[$key] = $value;
    }

    // 获取绑定实例，不存在时抛出异常
    public static function get(string $key): mixed
    {
        if (!array_key_exists($key, self::$bindings)) {
            throw new \RuntimeException('容器中找不到: ' . $key);
        }
        return self::$bindings[$key];
    }
}
