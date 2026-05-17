<?php
declare(strict_types=1);

namespace PHPFrame\Support;

final class Logger
{
    /**
     * @param array<string, mixed> $context
     */
    public static function info(string $channel, string $message, array $context = []): void
    {
        self::write('INFO', $channel, $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function error(string $channel, string $message, array $context = []): void
    {
        self::write('ERROR', $channel, $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private static function write(string $level, string $channel, string $message, array $context): void
    {
        try {
            $dir = __DIR__ . '/../../logs';
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }

            $date = date('Y-m-d');
            $file = $dir . '/' . preg_replace('/[^a-zA-Z0-9_.-]/', '_', $channel) . '-' . $date . '.log';

            $row = [
                'ts' => date('c'),
                'level' => $level,
                'channel' => $channel,
                'message' => $message,
            ];
            if ($context !== []) {
                $row['context'] = $context;
            }

            $line = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
            if ($line === false) {
                return;
            }

            $fp = @fopen($file, 'ab');
            if ($fp === false) {
                return;
            }
            @flock($fp, LOCK_EX);
            @fwrite($fp, $line);
            @flock($fp, LOCK_UN);
            @fclose($fp);
        } catch (\Throwable) {
            // Never break business flow because of logging.
        }
    }
}

