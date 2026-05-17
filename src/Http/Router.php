<?php
declare(strict_types=1);

namespace PHPFrame\Http;

use Closure;

// 路由器：支持静态路径和带参数路径（如 /post/{id}），按 HTTP 方法匹配
final class Router
{
    /** @var array<array{method: string, pattern: string, regex: string, paramNames: string[], handler: callable(Request): Response}> */
    private array $routes = [];
    private ?Closure $notFoundHandler = null;

    /**
     * 注册 GET 路由
     * @param callable(Request): Response $handler
     */
    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    /**
     * 注册 POST 路由
     * @param callable(Request): Response $handler
     */
    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    /**
     * 设置 404 处理器
     * @param callable(Request): Response $handler
     */
    public function setNotFoundHandler(callable $handler): void
    {
        $this->notFoundHandler = $handler(...);
    }

    /**
     * 将路径中的 {name} 占位符转换为正则，并存储路由信息
     * @param callable(Request): Response $handler
     */
    private function add(string $method, string $path, callable $handler): void
    {
        $paramNames = [];
        $regex = preg_replace_callback('/\{(\w+)\}/', function (array $m) use (&$paramNames): string {
            $paramNames[] = $m[1];
            return '([^/]+)';
        }, $path);
        $regex = '#^' . $regex . '$#';

        $this->routes[] = [
            'method' => $method,
            'pattern' => $path,
            'regex' => $regex,
            'paramNames' => $paramNames,
            'handler' => $handler,
        ];
    }

    // 分发请求：按注册顺序匹配路由，提取路径参数后调用处理器
    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }
            if (preg_match($route['regex'], $request->path, $matches)) {
                array_shift($matches); // 去掉完整匹配
                foreach ($route['paramNames'] as $i => $name) {
                    $request->params[$name] = $matches[$i] ?? '';
                }
                return ($route['handler'])($request);
            }
        }

        if ($this->notFoundHandler !== null) {
            return ($this->notFoundHandler)($request);
        }
        return Response::html('Not Found', 404);
    }
}
