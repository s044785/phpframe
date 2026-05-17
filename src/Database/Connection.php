<?php
declare(strict_types=1);

namespace PHPFrame\Database;

use PDO;

// 数据库连接管理（单例模式），封装 PDO 的常用操作
final class Connection
{
    private static ?self $instance = null;
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    // 通过 DSN 创建连接
    public static function fromDsn(string $dsn, string $user, string $pass): self
    {
        return new self(new PDO($dsn, $user, $pass));
    }

    // 设置全局数据库连接实例
    public static function setInstance(self $connection): void
    {
        self::$instance = $connection;
    }

    // 获取全局数据库连接实例
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new \RuntimeException('数据库连接未初始化');
        }
        return self::$instance;
    }

    // 获取原始 PDO 对象
    public function pdo(): PDO
    {
        return $this->pdo;
    }

    /**
     * 查询多行记录
     * @return array<int, array<string, mixed>>
     */
    public function select(string $sql, array $params = []): array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * 查询单行记录
     * @return array<string, mixed>|null
     */
    public function selectOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    // 查询单个标量值（如 COUNT 结果）
    public function scalar(string $sql, array $params = []): mixed
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    // 执行写操作（INSERT/UPDATE/DELETE），返回受影响行数
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    // 获取最后插入的 ID
    public function lastInsertId(): string
    {
        $id = $this->pdo->lastInsertId();
        return $id !== false ? $id : '0';
    }
}
