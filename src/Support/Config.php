<?php
declare(strict_types=1);

namespace PHPFrame\Support;

// 配置文件管理：加载 config 目录下的 PHP 配置文件，支持缓存
final class Config
{
    /** @var array<string, array<string, mixed>> 配置缓存 */
    private array $cache = [];

    public function __construct(private readonly string $baseDir)
    {
    }

    /**
     * 读取指定名称的配置（文件名不带 .php 后缀）
     * @return array<string, mixed>
     */
    public function get(string $name): array
    {
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }
        $path = rtrim($this->baseDir, '/') . '/' . $name . '.php';
        if (!is_file($path)) {
            throw new \RuntimeException('配置文件不存在: ' . $name);
        }
        $data = require $path;
        if (!is_array($data)) {
            throw new \RuntimeException('配置文件必须返回数组: ' . $name);
        }
        $this->cache[$name] = $data;
        return $data;
    }
}
