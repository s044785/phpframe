<?php
declare(strict_types=1);

namespace PHPFrame\Http;

final class Request
{
    /**
     * @param array<string, string> $query
     * @param array<string, string> $headers
     * @param array<string, mixed> $post
     * @param array<string, mixed> $server
     * @param array<string, string> $params  route path params
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

    public static function fromGlobals(): self
    {
        $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $uri = (string)($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $query = [];
        parse_str((string)(parse_url($uri, PHP_URL_QUERY) ?? ''), $query);

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

    public function header(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function param(string $name): ?string
    {
        return $this->params[$name] ?? null;
    }
}
