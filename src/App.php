<?php
declare(strict_types=1);

namespace PHPFrame;

use PHPFrame\Http\Request;
use PHPFrame\Http\Response;

final class App
{
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
