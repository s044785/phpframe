<?php
declare(strict_types=1);

namespace PHPFrame\Http;

// HTTP 响应对象：封装状态码、响应头和响应体
final class Response
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        public readonly int $status,
        public readonly array $headers,
        public readonly string $body,
    ) {
    }

    // 构建 HTML 响应
    public static function html(string $html, int $status = 200): self
    {
        return new self($status, ['content-type' => 'text/html; charset=utf-8'], $html);
    }

    /**
     * 构建 JSON 响应
     * @param array<string, mixed> $data
     */
    public static function json(array $data, int $status = 200): self
    {
        return new self(
            $status,
            ['content-type' => 'application/json; charset=utf-8'],
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}',
        );
    }

    // 构建重定向响应
    public static function redirect(string $location, int $status = 302): self
    {
        return new self($status, ['location' => $location], '');
    }

    // 将响应输出到浏览器
    public static function emit(self $response): void
    {
        http_response_code($response->status);
        foreach ($response->headers as $k => $v) {
            header($k . ': ' . $v);
        }
        echo $response->body;
    }
}
