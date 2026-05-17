<?php
declare(strict_types=1);

namespace PHPFrame\Support;

// 视图渲染：加载 views 目录下的 PHP 模板，传入变量并返回渲染后的 HTML
final class View
{
    /**
     * 渲染指定视图
     * @param string               $name 视图名称（不带 .php 后缀）
     * @param array<string, mixed> $data 传递给模板的变量
     */
    public static function render(string $name, array $data = []): string
    {
        $path = __DIR__ . '/../../views/' . $name . '.php';
        if (!is_file($path)) {
            throw new \RuntimeException('视图文件不存在: ' . $name);
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $path;
        return (string)ob_get_clean();
    }
}
