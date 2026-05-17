<?php
declare(strict_types=1);

namespace PHPFrame\Support;

use PHPFrame\Database\Connection;

final class Db
{
    public static function connectFromEnv(): Connection
    {
        $dsn = Env::require('DB_DSN');
        $user = Env::get('DB_USER', '') ?? '';
        $pass = Env::get('DB_PASS', '') ?? '';
        return Connection::fromDsn($dsn, $user, $pass);
    }

    public static function init(): void
    {
        Connection::setInstance(self::connectFromEnv());
    }
}
