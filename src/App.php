<?php
declare(strict_types=1);

namespace PHPFrame;

use PHPFrame\Http\Request;
use PHPFrame\Http\Response;

// 应用主类，负责启动会话、构建路由并分发请求
final class App
{
    // 启动框架：开启安全 Session → 构建路由表 → 分发请求 → 输出响应
    public static function run(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
            ]);
        }

        $router = Routes::build();

        $request = Request::fromGlobals();
        $response = $router->dispatch($request);

        Response::emit($response);
    }
}
