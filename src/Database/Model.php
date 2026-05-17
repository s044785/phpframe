<?php
declare(strict_types=1);

namespace PHPFrame\Database;

abstract class Model implements \ArrayAccess
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';
    public bool $timestamps = true;

    /** @var array<string, mixed> */
    private array $attributes = [];
    /** @var array<string, mixed> */
    private array $original = [];
    private bool $exists = false;

    // ── Static Query API ─────────────────────────────────────

    public static function query(): QueryBuilder
    {
        return new QueryBuilder(static::table());
    }

    public static function table(): string
    {
        if (static::$table !== '') {
            return static::$table;
        }
        // Default: pluralize + snake_case (Post → posts, BlogPost → blog_posts)
        $parts = explode('\\', static::class);
        $base = end($parts);
        $snake = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $base));
        return $snake . 's';
    }

    public static function find(mixed $id): ?static
    {
        $row = static::query()->where(static::$primaryKey, $id)->first();
        return $row !== null ? static::hydrate($row) : null;
    }

    /**
     * @return static[]
     */
    public static function all(): array
    {
        $rows = static::query()->get();
        return array_map(fn($row) => static::hydrate($row), $rows);
    }

    public static function where(string $column, mixed $operator, mixed $value = null): QueryBuilder
    {
        return static::query()->where($column, $operator, $value);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function create(array $data): static
    {
        $model = new static();
        $model->fill($data);
        $model->save();
        return $model;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function updateOrCreate(array $attributes, array $values): static
    {
        $query = static::query();
        foreach ($attributes as $col => $val) {
            $query->where($col, $val);
        }
        $row = $query->first();
        if ($row !== null) {
            $model = static::hydrate($row);
            $model->fill($values);
            $model->save();
            return $model;
        }
        return static::create(array_merge($attributes, $values));
    }

    /**
     * @param array<string, mixed> $row
     */
    protected static function hydrate(array $row): static
    {
        $model = new static();
        $model->attributes = $row;
        $model->original = $row;
        $model->exists = true;
        return $model;
    }

    // ── Instance API ─────────────────────────────────────────

    /**
     * @param array<string, mixed> $data
     */
    public function fill(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->attributes[$key] = $value;
        }
    }

    public function save(): bool
    {
        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            if (!$this->exists) {
                $this->attributes['created_at'] = $now;
            }
            $this->attributes['updated_at'] = $now;
        }

        if ($this->exists) {
            $pk = static::$primaryKey;
            $pkVal = $this->attributes[$pk] ?? null;
            $dirty = array_diff_assoc($this->attributes, $this->original);
            if ($dirty === []) {
                return true;
            }
            static::query()->where($pk, $pkVal)->update($dirty);
            $this->original = $this->attributes;
        } else {
            $id = static::query()->insert($this->attributes);
            if ($id > 0 && !isset($this->attributes[static::$primaryKey])) {
                $this->attributes[static::$primaryKey] = $id;
            }
            $this->original = $this->attributes;
            $this->exists = true;
        }

        return true;
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }
        $pk = static::$primaryKey;
        $pkVal = $this->attributes[$pk] ?? null;
        if ($pkVal === null) {
            return false;
        }
        static::query()->where($pk, $pkVal)->delete();
        $this->exists = false;
        return true;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(array $data): bool
    {
        $this->fill($data);
        return $this->save();
    }

    public function exists(): bool
    {
        return $this->exists;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    // ── Magic ────────────────────────────────────────────────

    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->__isset($offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->__get($offset);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->__set($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->attributes[$offset]);
    }
}
