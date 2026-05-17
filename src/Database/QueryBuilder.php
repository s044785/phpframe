<?php
declare(strict_types=1);

namespace PHPFrame\Database;

final class QueryBuilder
{
    private string $table;
    private string $as = '';
    /** @var array<int, array{0: string, 1: string, 2: mixed}> */
    private array $wheres = [];
    /** @var array<int, array{0: string, 1: string}> */
    private array $orders = [];
    private ?int $limit = null;
    private ?int $offset = null;

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    public function from(string $table): self
    {
        $this->table = $table;
        return $this;
    }

    public function where(string $column, string $operator, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        $this->wheres[] = ['AND', $column, $operator, $value];
        return $this;
    }

    public function orWhere(string $column, string $operator, mixed $value = null): self
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        $this->wheres[] = ['OR', $column, $operator, $value];
        return $this;
    }

    /**
     * @param array<int, mixed> $values
     */
    public function whereIn(string $column, array $values): self
    {
        $placeholders = implode(', ', array_fill(0, count($values), '?'));
        $this->wheres[] = ['AND', $column, 'IN', $values, "($placeholders)"];
        return $this;
    }

    public function orderBy(string $column, string $direction = 'ASC'): self
    {
        $this->orders[] = [$column, strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC'];
        return $this;
    }

    public function latest(string $column = 'created_at'): self
    {
        $this->orders[] = [$column, 'DESC'];
        return $this;
    }

    public function oldest(string $column = 'created_at'): self
    {
        $this->orders[] = [$column, 'ASC'];
        return $this;
    }

    public function limit(int $limit): self
    {
        $this->limit = $limit;
        return $this;
    }

    public function offset(int $offset): self
    {
        $this->offset = $offset;
        return $this;
    }

    // ── Execution ────────────────────────────────────────────

    /**
     * @return array<int, array<string, mixed>>
     */
    public function get(): array
    {
        [$sql, $params] = $this->buildSelect();
        return Connection::getInstance()->select($sql, $params);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        $this->limit = 1;
        $rows = $this->get();
        return $rows[0] ?? null;
    }

    public function count(): int
    {
        [$sql, $params] = $this->buildCount();
        return (int) Connection::getInstance()->scalar($sql, $params);
    }

    public function exists(): bool
    {
        return $this->count() > 0;
    }

    /**
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
     * @param array<string, mixed> $data
     */
    public function update(array $data): int
    {
        $sets = implode(', ', array_map(fn($col) => "{$col} = ?", array_keys($data)));
        [$whereClause, $whereParams] = $this->buildWhereClause();
        $sql = "UPDATE {$this->table} SET {$sets}" . ($whereClause !== '' ? " WHERE {$whereClause}" : '');
        return Connection::getInstance()->execute($sql, [...array_values($data), ...$whereParams]);
    }

    public function delete(): int
    {
        [$whereClause, $whereParams] = $this->buildWhereClause();
        $sql = "DELETE FROM {$this->table}" . ($whereClause !== '' ? " WHERE {$whereClause}" : '');
        return Connection::getInstance()->execute($sql, $whereParams);
    }

    // ── SQL Building ─────────────────────────────────────────

    /**
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
                $placeholders = $where[4] ?? throw new \RuntimeException('IN clause missing placeholders');
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
