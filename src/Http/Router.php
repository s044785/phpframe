<?php
declare(strict_types=1);

namespace PHPFrame\Http;

use Closure;
use InvalidArgumentException;
use RuntimeException;

// 路由器：支持静态路径、带参数路径（如 /post/{id}）、路由分组、命名路由、URL 生成
final class Router
{
    private static ?self $instance = null;

    /**
     * @var array<array{method: string, pattern: string, regex: string, paramNames: string[], handler: callable(Request): Response, name: ?string}>
     */
    private array $routes = [];

    /**
     * 命名路由索引：name → route entry
     * @var array<string, array{pattern: string, regex: string, paramNames: string[]}>
     */
    private array $namedRoutes = [];

    /** 当前分组前缀栈（支持嵌套分组） */
    private string $groupPrefix = '';

    private ?Closure $notFoundHandler = null;

    // ========== 静态实例（供视图中直接调用 url()） ==========

    /**
     * 设置当前 Router 实例（在 Routes::build() 中调用）
     */
    public static function setInstance(self $router): void
    {
        self::$instance = $router;
    }

    /**
     * 获取当前 Router 实例
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            throw new RuntimeException('Router 实例尚未初始化，请先调用 Routes::build()');
        }
        return self::$instance;
    }

    // ========== HTTP 方法快捷注册 ==========

    /**
     * 注册 GET 路由
     * @param callable(Request): Response $handler
     */
    public function get(string $path, callable $handler, ?string $name = null): self
    {
        return $this->add('GET', $path, $handler, $name);
    }

    /**
     * 注册 POST 路由
     * @param callable(Request): Response $handler
     */
    public function post(string $path, callable $handler, ?string $name = null): self
    {
        return $this->add('POST', $path, $handler, $name);
    }

    /**
     * 注册 PUT 路由
     * @param callable(Request): Response $handler
     */
    public function put(string $path, callable $handler, ?string $name = null): self
    {
        return $this->add('PUT', $path, $handler, $name);
    }

    /**
     * 注册 PATCH 路由
     * @param callable(Request): Response $handler
     */
    public function patch(string $path, callable $handler, ?string $name = null): self
    {
        return $this->add('PATCH', $path, $handler, $name);
    }

    /**
     * 注册 DELETE 路由
     * @param callable(Request): Response $handler
     */
    public function delete(string $path, callable $handler, ?string $name = null): self
    {
        return $this->add('DELETE', $path, $handler, $name);
    }

    /**
     * 注册匹配多个 HTTP 方法的路由
     * @param string[] $methods
     * @param callable(Request): Response $handler
     */
    public function map(array $methods, string $path, callable $handler, ?string $name = null): self
    {
        foreach ($methods as $method) {
            $this->add(strtoupper($method), $path, $handler, $name);
        }
        return $this;
    }

    // ========== 路由分组 ==========

    /**
     * 路由分组：在指定路径前缀下批量注册路由
     *
     * 回调接收当前 Router 实例，分组内注册的路由自动拼接前缀。
     * 分组支持嵌套，内层前缀会叠加在外层前缀之后。
     *
     * @param callable(Router): void $callback
     *
     * 示例：
     *   $router->group('/api', function(Router $r) {
     *       $r->get('/users', [ApiController::class, 'list'], 'api.users');
     *       $r->group('/v2', function(Router $r) {
     *           $r->get('/users', [ApiV2Controller::class, 'list']);
     *       });
     *   });
     */
    public function group(string $prefix, callable $callback): self
    {
        // 保存当前前缀，追加新层级
        $previousPrefix = $this->groupPrefix;
        $this->groupPrefix .= '/' . trim($prefix, '/');

        $callback($this);

        // 恢复上层前缀
        $this->groupPrefix = $previousPrefix;
        return $this;
    }

    // ========== 命名路由 / URL 生成 ==========

    /**
     * 根据路由名称生成 URL
     *
     * @param string $name   路由名称
     * @param array<string, string|int> $params  路径参数（如 ['id' => 5]）
     * @param array<string, string> $query  URL 查询参数（如 ['page' => 2]）
     * @return string 生成的路径（如 "/post/5"）
     *
     * @throws RuntimeException 路由名称不存在时
     */
    public function url(string $name, array $params = [], ?array $query = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new RuntimeException("命名路由不存在: {$name}");
        }

        $route = $this->namedRoutes[$name];
        $path = $route['pattern'];

        foreach ($route['paramNames'] as $paramName) {
            if (!array_key_exists($paramName, $params)) {
                throw new InvalidArgumentException(
                    "URL 生成缺少参数 \"{$paramName}\"，路由: {$name}"
                );
            }
            $path = str_replace(
                '{' . $paramName . '}',
                (string)$params[$paramName],
                $path
            );
        }
        if ($query) {
            $path .= '?' . http_build_query($query);
        }

        return $path;
    }

    /**
     * 检查命名路由是否存在
     */
    public function hasRoute(string $name): bool
    {
        return isset($this->namedRoutes[$name]);
    }

    /**
     * 获取所有命名路由的名称列表
     * @return string[]
     */
    public function routeNames(): array
    {
        return array_keys($this->namedRoutes);
    }

    // ========== 404 处理 ==========

    /**
     * 设置 404 处理器
     * @param callable(Request): Response $handler
     */
    public function setNotFoundHandler(callable $handler): void
    {
        $this->notFoundHandler = $handler(...);
    }

    // ========== 分发 ==========

    /**
     * 分发请求：按注册顺序匹配路由，提取路径参数后调用处理器
     */
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

    // ========== 内部方法 ==========

    /**
     * 将路径中的 {name} 占位符转换为正则，并存储路由信息
     * @param callable(Request): Response $handler
     */
    private function add(string $method, string $path, callable $handler, ?string $name): self
    {
        // 拼接分组前缀
        $fullPath = $this->groupPrefix . '/' . trim($path, '/');
        $fullPath = $fullPath === '' ? '/' : $fullPath;

        // 检查命名路由重复
        if ($name !== null && isset($this->namedRoutes[$name])) {
            throw new RuntimeException("路由名称 \"{$name}\" 已存在");
        }

        $paramNames = [];
        $regex = preg_replace_callback('/\{(\w+)\}/', function (array $m) use (&$paramNames): string {
            $paramNames[] = $m[1];
            return '([^/]+)';
        }, $fullPath);
        $regex = '#^' . $regex . '$#';

        $route = [
            'method' => $method,
            'pattern' => $fullPath,
            'regex' => $regex,
            'paramNames' => $paramNames,
            'handler' => $handler,
            'name' => $name,
        ];

        $this->routes[] = $route;

        if ($name !== null) {
            $this->namedRoutes[$name] = $route;
        }

        return $this;
    }
}
