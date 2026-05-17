<?php
declare(strict_types=1);

namespace PHPFrame\Support;

// Redis 连接管理（单例），封装常用缓存操作
final class Redis
{
    private static ?self $instance = null;
    private \Redis $redis;

    public function __construct(\Redis $redis)
    {
        $this->redis = $redis;
    }

    // 从环境变量创建 Redis 连接
    public static function fromEnv(): self
    {
        $host = Env::get('REDIS_HOST', '127.0.0.1') ?? '127.0.0.1';
        $port = (int)(Env::get('REDIS_PORT', '6379') ?? '6379');
        $pass = Env::get('REDIS_PASS', '');

        $redis = new \Redis();
        $redis->connect($host, $port);
        if ($pass !== null && $pass !== '') {
            $redis->auth($pass);
        }

        return new self($redis);
    }

    // 设置全局 Redis 实例
    public static function setInstance(self $instance): void
    {
        self::$instance = $instance;
    }

    // 获取全局 Redis 实例
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = self::fromEnv();
        }
        return self::$instance;
    }

    // 初始化（在 bootstrap 中调用）
    public static function init(): void
    {
        self::$instance = null; // 重置以便延迟连接
    }

    // ── 缓存操作 ──────────────────────────────────────────────

    // 读取缓存
    public function get(string $key): mixed
    {
        $value = $this->redis->get($key);
        if ($value === false) {
            return null;
        }
        $decoded = json_decode($value, true);
        return $decoded !== null ? $decoded : $value;
    }

    // 写入缓存（带过期时间，秒）
    public function set(string $key, mixed $value, int $ttlSeconds = 0): bool
    {
        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            return false;
        }
        if ($ttlSeconds > 0) {
            return $this->redis->setex($key, $ttlSeconds, $encoded);
        }
        return $this->redis->set($key, $encoded);
    }

    // 删除缓存
    public function del(string $key): bool
    {
        return (bool) $this->redis->del($key);
    }

    // 判断 key 是否存在
    public function exists(string $key): bool
    {
        return (bool) $this->redis->exists($key);
    }

    // 获取原始 phpredis 对象
    public function raw(): \Redis
    {
        return $this->redis;
    }

    // 禁止克隆
    private function __clone() {}
}
