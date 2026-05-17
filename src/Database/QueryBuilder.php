<?php
declare(strict_types=1);

namespace PHPFrame\Database;

// 查询构建器：链式调用构建 SQL，所有值通过参数绑定，防止 SQL 注入
final class QueryBuilder
{
    private string $table;
    // 关联的 Model 类名（设置后 get/first 会自动水合为 Model 实例）
    private ?string $modelClass = null;
    /** @var array<int, array<int, mixed>> */
    private array $wheres = [];
    /** @var array<int, array{0: string, 1: string}> */
    private array $orders = [];
    private ?int $limit = null;
    private ?int $offset = null;

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    // 设置要水合的 Model 类名，设置后 get() 和 first() 返回 Model 实例而非数组
    public function setModel(string $modelClass): self
    {
        $this->modelClass = $modelClass;
        return $this;
    }

    // 动态切换操作的表名
    public function from(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    /**
     * 添加 WHERE 条件（AND 连接）
     * 支持两参数简写：where('status', 'active') 等价于 where('status', '=', 'active')
     */
    public function where(string $column, mixed $operator, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        $this->wheres[] = ['AND', $column, $operator, $value];
        return $this;
    }

    /**
     * 添加 WHERE 条件（OR 连接）
     * 支持两参数简写：orWhere('status', 'active') 等价于 orWhere('status', '=', 'active')
     */
    public function orWhere(string $column, mixed $operator, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        $this->wheres[] = ['OR', $column, $operator, $value];
        return $this;
    }

    /**
     * WHERE IN 条件
     * @param array<int, mixed> $values
     */
    public function whereIn(string $column, array $values): self
    {
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = ['AND', $column, 'IN', $values, "($placeholders)"];
        return $this;
    }

    // 添加排序规则
    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orders[] = [$column, strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC'];
        return $this;
    }

    // 按创建时间倒序（最新的在前）
    public function latest(string $column = 'created_at'): self
    {
        $this->orders[] = [$column, 'DESC'];
        return $this;
    }

    // 按创建时间正序（最早的在前）
    public function oldest(string $column = 'created_at'): self
    {
        $this->orders[] = [$column, 'ASC'];
        return $this;
    }

    // 限制返回行数
    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    // 设置偏移量（用于分页）
    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    // ── 执行方法 ──────────────────────────────────────────────

    /**
     * 执行查询，返回所有匹配行；若设置了 modelClass 则返回 Model 实例数组
     * @return array<int, array<string, mixed>|object>
     */
    public function get(): array
    {
        [$sql, $params] = $this->buildSelect();
        $rows = Connection::getInstance()->select($sql, $params);
        if ($this->modelClass !== null && $rows !== []) {
            return array_map(fn($row) => $this->modelClass::hydrate($row), $rows);
        }
        return $rows;
    }

    /**
     * 执行查询，只返回第一行；若设置了 modelClass 则返回 Model 实例
     * @return array<string, mixed>|object|null
     */
    public function first(): mixed
    {
        $this->limit = 1;
        $rows = $this->get();
        return $rows[0] ?? null;
    }

    // 返回匹配记录数
    public function count(): int
    {
        [$sql, $params] = $this->buildCount();
        return (int) Connection::getInstance()->scalar($sql, $params);
    }

    // 判断是否存在匹配记录
    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
     * 插入新记录，返回自增 ID
     * @param array<string, mixed> $data
     */
    public function insert(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        Connection::getInstance()->execute($sql, array_values($data));
        return (int) Connection::getInstance()->lastInsertId();
    }

    /**
     * 更新记录，返回受影响行数
     * @param array<string, mixed> $data
     */
    public function update(array $data): int
    {
        $sets = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        [$whereClause, $whereParams] = $this->buildWhereClause();
        $sql = "UPDATE {$this->table} SET {$sets}" . ($whereClause !== '' ? " WHERE {$whereClause}" : '');
        return Connection::getInstance()->execute($sql, [...array_values($data), ...$whereParams]);
    }

    // 删除记录，返回受影响行数
    public function delete(): int
    {
        [$whereClause, $whereParams] = $this->buildWhereClause();
        $sql = "DELETE FROM {$this->table}" . ($whereClause !== '' ? " WHERE {$whereClause}" : '');
        return Connection::getInstance()->execute($sql, $whereParams);
    }

    // ── SQL 构建（内部方法）─────────────────────────────────────

    /**
     * 构建 SELECT 语句
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function buildSelect(): array
    {
        [$whereClause, $whereParams] = $this->buildWhereClause();

        $sql = "SELECT * FROM {$this->table}";
        if ($whereClause !== '') {
            $sql .= " WHERE {$whereClause}";
        }
        if ($this->orders !== []) {
            $orderParts = implode(', ', array_map(fn($o) => "{$o[0]} {$o[1]}", $this->orders));
            $sql .= " ORDER BY {$orderParts}";
        }
        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }
        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return [$sql, $whereParams];
    }

    /**
     * 构建 COUNT 语句
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function buildCount(): array
    {
        [$whereClause, $whereParams] = $this->buildWhereClause();
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        if ($whereClause !== '') {
            $sql .= " WHERE {$whereClause}";
        }
        return [$sql, $whereParams];
    }

    /**
     * 构建 WHERE 子句，返回 SQL 片段和参数数组
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function buildWhereClause(): array
    {
        $clauses = [];
        $params = [];

        foreach ($this->wheres as $i => $where) {
            [$bool, $column, $operator, $value] = $where;
            $prefix = $i === 0 ? '' : "{$bool} ";

            if ($operator === 'IN') {
                // whereIn 的 wheres 结构多一个占位符字符串：[AND, column, 'IN', values, '(?, ?)']
                $placeholders = $where[4] ?? throw new \RuntimeException('IN 子句缺少占位符');
                $clauses[] = "{$prefix}{$column} IN {$placeholders}";
                array_push($params, ...$value);
            } else {
                $clauses[] = "{$prefix}{$column} {$operator} ?";
                $params[] = $value;
            }
        }

        return [implode(' ', $clauses), $params];
    }
}
