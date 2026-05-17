<?php
declare(strict_types=1);

namespace PHPFrame\Support;

final class Config
{
    /** @var array<string, array<string, mixed>> */
    private array $cache = [];

    public function __construct(private readonly string $baseDir)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function get(string $name): array
    {
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }
        $path = rtrim($this->baseDir, '/') . '/' . $name . '.php';
        if (!is_file($path)) {
            throw new \RuntimeException('Config not found: ' . $name);
        }
        $data = require $path;
        if (!is_array($data)) {
            throw new \RuntimeException('Config must return array: ' . $name);
        }
        $this->cache[$name] = $data;
        return $data;
    }
}

