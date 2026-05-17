<?php
declare(strict_types=1);

namespace PHPFrame\Database;

// Eloquent 风格的基类 Model，提供查询构建和实例持久化能力
abstract class Model implements \ArrayAccess
{
    // 子类可覆盖：表名（留空则自动推导）
    protected static string $table = '';
    // 子类可覆盖：主键字段名
    protected static string $primaryKey = 'id';
    // 是否自动维护 created_at / updated_at 时间戳
    public bool $timestamps = true;

    /** @var array<string, mixed> 当前属性 */
    private array $attributes = [];
    /** @var array<string, mixed> 原始属性（用于脏检测） */
    private array $original = [];
    // 该实例是否对应数据库中的一条记录
    private bool $exists = false;

    // ── 静态查询方法 ──────────────────────────────────────────

    // 创建 QueryBuilder 实例，自动绑定当前 Model 类以启用结果水合
    public static function query(): QueryBuilder
    {
        return (new QueryBuilder(static::table()))->setModel(static::class);
    }

    // 获取表名：优先使用子类定义的 $table，否则从类名自动推导
    public static function table(): string
    {
        if (static::$table !== '') {
            return static::$table;
        }
        // 自动推导规则：Post → posts, BlogPost → blog_posts
        $parts = explode('\\', static::class);
        $base = end($parts);
        $snake = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $base));
        return $snake . 's';
    }

    // 根据主键查找单条记录（query() 已绑定模型，first() 自动水合）
    public static function find(mixed $id): ?static
    {
        return static::query()->where(static::$primaryKey, $id)->first();
    }

    /**
     * 获取表中所有记录（query() 已绑定模型，get() 自动水合）
     * @return static[]
     */
    public static function all(): array
    {
        return static::query()->get();
    }

    // 快捷创建 QueryBuilder 并添加 WHERE 条件
    public static function where(string $column, mixed $operator, mixed $value = null): QueryBuilder
    {
        return static::query()->where($column, $operator, $value);
    }

    /**
     * 创建并保存一条新记录
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
     * 根据条件查找，存在则更新，不存在则创建
     * @param array<string, mixed> $attributes 查找条件
     * @param array<string, mixed> $values     要更新或创建的字段
     */
    public static function updateOrCreate(array $attributes, array $values): static
    {
        $query = static::query();
        foreach ($attributes as $col => $val) {
            $query->where($col, $val);
        }
        $model = $query->first();
        if ($model !== null) {
            $model->fill($values);
            $model->save();
            return $model;
        }
        return static::create(array_merge($attributes, $values));
    }

    /**
     * 将数据库行数据水合为 Model 实例
     * @param array<string, mixed> $row
     */
    public static function hydrate(array $row): static
    {
        $model = new static();
        $model->attributes = $row;
        $model->original = $row;
        $model->exists = true;
        return $model;
    }

    // ── 实例方法 ──────────────────────────────────────────────

    /**
     * 批量填充属性（不立即保存）
     * @param array<string, mixed> $data
     */
    public function fill(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->attributes[$key] = $value;
        }
    }

    // 保存：存在则更新脏字段，不存在则插入
    public function save(): bool
    {
        // 自动维护时间戳
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
            // 只更新有变化的字段
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

    // 删除数据库中的对应记录
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
     * 更新部分字段并立即保存
     * @param array<string, mixed> $data
     */
    public function update(array $data): bool
    {
        $this->fill($data);
        return $this->save();
    }

    // 该实例是否对应数据库中的记录
    public function exists(): bool
    {
        return $this->exists;
    }

    /**
     * 将属性转为数组
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    // ── 魔术访问器 ────────────────────────────────────────────

    // 通过 $model->field 访问属性
    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    // 通过 $model->field = value 设置属性
    public function __set(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    // ArrayAccess 接口实现，支持 $model['field'] 访问
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
