<?php
declare(strict_types=1);

namespace PHPFrame\Database;

// 分页结果，包含当前页数据条和分页元信息
final class Paginator
{
    /**
     * @param array<int, mixed> $items       当前页的数据
     * @param int               $total       总记录数
     * @param int               $perPage     每页条数
     * @param int               $currentPage 当前页码
     */
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $perPage,
        public readonly int $currentPage,
    ) {
    }

    // 总页数
    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / $this->perPage));
    }

    // 是否有上一页
    public function hasPrev(): bool
    {
        return $this->currentPage > 1;
    }

    // 是否有下一页
    public function hasNext(): bool
    {
        return $this->currentPage < $this->lastPage();
    }

    // 上一页页码
    public function prevPage(): int
    {
        return max(1, $this->currentPage - 1);
    }

    // 下一页页码
    public function nextPage(): int
    {
        return min($this->lastPage(), $this->currentPage + 1);
    }
}
