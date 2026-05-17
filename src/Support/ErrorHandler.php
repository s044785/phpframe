<?php
declare(strict_types=1);

namespace PHPFrame\Support;

// 全局错误/异常处理器，开发环境显示详细错误，生产环境隐藏
final class ErrorHandler
{
    // 注册错误处理（仅对 Web 请求生效，CLI 模式跳过）
    public static function register(): void
    {
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            return;
        }

        error_reporting(E_ALL);
        ini_set('display_errors', Env::get('APP_ENV', 'prod') === 'dev' ? '1' : '0');

        // 捕获未处理的异常
        set_exception_handler(function (\Throwable $e): void {
            http_response_code(500);
            header('content-type: text/plain; charset=utf-8');
            echo "Internal Server Error\n";
            if (Env::get('APP_ENV', 'prod') === 'dev') {
                echo $e->getMessage() . "\n" . $e->getTraceAsString();
            }
        });
    }
}
