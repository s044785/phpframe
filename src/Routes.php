<?php
declare(strict_types=1);

namespace PHPFrame;

use PHPFrame\Http\Response;
use PHPFrame\Http\Router;
use PHPFrame\Support\View;

final class Routes
{
    public static function build(): Router
    {
        $router = new Router();

        $router->get('/', fn () => Response::html(
            View::render('layout', [
                'title' => 'PHPFrame',
                'content' => View::render('home'),
            ])
        ));

        $router->setNotFoundHandler(fn () => Response::html('Not Found', 404));
        return $router;
    }
}
