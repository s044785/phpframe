<?php
declare(strict_types=1);

namespace PHPFrame\Support;

use PHPFrame\Database\Connection;

// 数据库辅助类：从环境变量读取配置并初始化连接
final class Db
{
    // 从 .env 中读取 DB_DSN / DB_USER / DB_PASS 创建连接
    public static function connectFromEnv(): Connection
    {
        $dsn = Env::require('DB_DSN');
        $user = Env::get('DB_USER', '') ?? '';
        $pass = Env::get('DB_PASS', '') ?? '';
        return Connection::fromDsn($dsn, $user, $pass);
    }

    // 初始化数据库连接，注册到全局 Connection 单例
    public static function init(): void
    {
        Connection::setInstance(self::connectFromEnv());
    }
}
