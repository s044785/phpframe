<?php
declare(strict_types=1);

// 框架引导文件：自动加载 → 环境变量 → 错误处理 → 数据库初始化

$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($composerAutoload)) {
    require $composerAutoload;
} else {
    // Composer 未安装时的回退自动加载方案
    spl_autoload_register(static function (string $class): void {
        $prefix = 'PHPFrame\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $rel = substr($class, strlen($prefix));
        $path = __DIR__ . '/' . str_replace('\\', '/', $rel) . '.php';
        if (is_file($path)) {
            require $path;
        }
    });
}

PHPFrame\Support\Env::load(__DIR__ . '/../.env');
PHPFrame\Support\ErrorHandler::register();
PHPFrame\Support\Db::init();
PHPFrame\Support\Redis::init();
