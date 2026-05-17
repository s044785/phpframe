<?php
declare(strict_types=1);

namespace PHPFrame\Http;

use Closure;

final class Router
{
    /** @var array<array{method: string, pattern: string, regex: string, paramNames: string[], handler: callable(Request): Response}> */
    private array $routes = [];
    private ?Closure $notFoundHandler = null;

    /**
     * @param callable(Request): Response $handler
     */
    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    /**
     * @param callable(Request): Response $handler
     */
    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    /**
     * @param callable(Request): Response $handler
     */
    public function setNotFoundHandler(callable $handler): void
    {
        $this->notFoundHandler = $handler(...);
    }

    /**
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

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }
            if (preg_match($route['regex'], $request->path, $matches)) {
                array_shift($matches); // full match
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
