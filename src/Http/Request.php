<?php
declare(strict_types=1);

namespace PHPFrame\Http;

// HTTP 请求对象：封装方法、路径、查询参数、请求头、请求体等信息
final class Request
{
    /**
     * @param array<string, string> $query    URL 查询参数
     * @param array<string, string> $headers  请求头（键名小写）
     * @param array<string, mixed>  $post     POST 请求体数据
     * @param array<string, mixed>  $server   $_SERVER 原始数据
     * @param array<string, string> $params   路由路径参数（由 Router 注入）
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $headers,
        public readonly string $rawBody,
        public readonly array $post,
        public readonly array $server,
        public array $params = [],
    ) {
    }

    // 从 PHP 超全局变量创建 Request 实例
    public static function fromGlobals(): self
    {
        $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $query = [];
        parse_str((string)(parse_url($uri, PHP_URL_QUERY) ?? ''), $query);

        // 提取 HTTP 请求头
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (!is_string($value)) {
                continue;
            }
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace('_', '-', strtolower(substr($key, 5)));
                $headers[$name] = $value;
            }
        }

        $rawBody = (string)file_get_contents('php://input');

        // 自动解析 JSON 请求体
        $post = $_POST;
        if ($method === 'POST' && ($post === [] || $post === null)) {
            $ct = strtolower((string)($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? ''));
            if (str_contains($ct, 'application/json')) {
                $data = json_decode($rawBody, true);
                if (is_array($data)) {
                    $post = $data;
                }
            }
        }

        return new self(
            $method,
            $path,
            array_map('strval', $query),
            $headers,
            $rawBody,
            $post,
            $_SERVER,
        );
    }

    // 获取指定请求头的值
    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    // 获取路由路径参数（如 /post/{id} 中的 id）
    public function param(string $name): ?string
    {
        return $this->params[$name] ?? null;
    }
}
