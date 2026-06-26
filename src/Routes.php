<?php
declare(strict_types=1);

namespace PHPFrame;

use PHPFrame\Controllers\ApiController;
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

        // ========== 基础路由（命名路由） ==========

        // 首页 — 命名路由，可通过 $router->url('home') 生成 "/"
        // 将 $router 传入视图，模板中可用 $router->url('路由名') 生成链接
        $router->get('/', function () use ($router) {
            return Response::html(
                View::render('layout', [
                    'title' => 'PHPFrame',
                    'content' => View::render('home', [
                        'router' => $router,
                    ]),
                ])
            );
        }, 'home');

        // ========== 路由分组示例 ==========

        // 所有 /api 下的接口归为一组，支持嵌套
        $router->group('/api', function (Router $r) {
            $controller = new ApiController();

            // GET  /api/success
            $r->get('/success', [$controller, 'success'], 'api.success');

            // GET  /api/error
            $r->get('/error', [$controller, 'error'], 'api.error');

            // GET  /api/test?q=...
            $r->get('/test', [$controller, 'test'], 'api.test');

            $r->get('/url/{id}.html', [$controller, 'url'], 'api.url');

            // 嵌套分组：/api/v1/...
            $r->group('/v1', function (Router $r) {
                // GET  /api/v1/status
                $r->get('/status', fn () => Response::success(['version' => '1.0']), 'api.v1.status');
            });
        });

        // ========== RESTful 多方法路由示例 ==========

        // 用命名路由 + URL 生成配合使用
        // GET    /post/{id}  → 文章详情
        // PUT    /post/{id}  → 更新文章
        // DELETE /post/{id}  → 删除文章
        $router->get('/post/{id}', fn ($req) =>
            Response::success(['id' => $req->param('id'), 'action' => 'show']),
            'post.show'
        );

        $router->put('/post/{id}', fn ($req) =>
            Response::success(['id' => $req->param('id'), 'action' => 'update']),
            'post.update'
        );

        $router->delete('/post/{id}', fn ($req) =>
            Response::success(['id' => $req->param('id'), 'action' => 'delete']),
            'post.delete'
        );

        // ========== 404 处理 ==========
        $router->setNotFoundHandler(fn () => Response::html('Not Found', 404));

        return $router;
    }
}
