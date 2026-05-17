<?php
declare(strict_types=1);

namespace PHPFrame;

use PHPFrame\Http\Response;
use PHPFrame\Http\Router;
use PHPFrame\Support\View;

// 路由注册文件，所有 URL 路由在此集中定义
final class Routes
{
    // 构建路由表，返回配置好的 Router 实例
    public static function build(): Router
    {
        $router = new Router();

        // 首页
        $router->get('/', fn () => Response::html(
            View::render('layout', [
                'title' => 'PHPFrame',
                'content' => View::render('home'),
            ])
        ));

        // 404 处理
        $router->setNotFoundHandler(fn () => Response::html('Not Found', 404));
        return $router;
    }
}
