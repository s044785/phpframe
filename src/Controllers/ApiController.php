<?php

declare(strict_types=1);

namespace PHPFrame\Controllers;

use PHPFrame\Http\Request;
use PHPFrame\Http\Response;
use PHPFrame\Support\Redis;
use PHPFrame\Support\Logger;

final class ApiController
{

    public function success(Request $r): Response
    {
        return Response::success([
            'id' => 1,
            'title' => 'hello world',
            'content' => 'this is a test',

        ]);
    }
    public function error(Request $r): Response
    {
        return Response::fail('error message');
    }

    public function test(Request $r): Response
    {
        $q = trim((string)($r->query['q'] ?? ''));
        if ($q === '110') {
            return Response::success(['message' => 'test']);
        }
        return Response::fail('Invalid query parameter');
    }

    public function url(Request $r): Response
    {
        return Response::success(['id' => $r->params['id']]);
    }
}
