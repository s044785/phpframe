<?php
declare(strict_types=1);

$composerAutoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($composerAutoload)) {
    require $composerAutoload;
} else {
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
