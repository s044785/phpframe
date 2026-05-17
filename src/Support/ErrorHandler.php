<?php
declare(strict_types=1);

namespace PHPFrame\Support;

final class ErrorHandler
{
    public static function register(): void
    {
        if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
            return;
        }

        error_reporting(E_ALL);
        ini_set('display_errors', Env::get('APP_ENV', 'prod') === 'dev' ? '1' : '0');

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
