<?php
declare(strict_types=1);

namespace PHPFrame\Support;

// 邮件发送：优先 SMTP，其次 mail()，开发环境写入日志
final class Mail
{
    /**
     * 发送邮件
     * @param string $to      收件人邮箱
     * @param string $subject 邮件主题
     * @param string $body    邮件正文（支持 HTML）
     */
    public static function send(string $to, string $subject, string $body): bool
    {
        Logger::info('mail', "To: {$to} | Subject: {$subject}\n{$body}");

        $host = Env::get('MAIL_HOST');

        if ($host !== null && $host !== '') {
            return self::sendSmtp($to, $subject, $body);
        }

        // 未配置 SMTP 时尝试系统 mail()
        $from = Env::get('MAIL_FROM', 'noreply@phpframe.local') ?? 'noreply@phpframe.local';
        $fromName = Env::get('MAIL_FROM_NAME', 'PHPFrame') ?? 'PHPFrame';
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: ' . $fromName . ' <' . $from . '>',
        ];
        @mail($to, $subject, $body, implode("\r\n", $headers));
        return true;
    }

    // ── SMTP 发送 ────────────────────────────────────────────

    private static function sendSmtp(string $to, string $subject, string $body): bool
    {
        $host    = Env::require('MAIL_HOST');
        $port    = (int)(Env::get('MAIL_PORT', '587') ?? '587');
        $user    = Env::require('MAIL_USER');
        $pass    = Env::require('MAIL_PASS');
        $from    = Env::get('MAIL_FROM', $user) ?? $user;
        $fromName = Env::get('MAIL_FROM_NAME', 'PHPFrame') ?? 'PHPFrame';
        $secure  = (Env::get('MAIL_SECURE', 'tls') ?? 'tls');
        $charset = Env::get('MAIL_CHARSET', 'UTF-8') ?? 'UTF-8';

        $errno = 0;
        $errstr = '';
        $prefix = ($secure === 'ssl') ? 'ssl://' : '';
        $ctx = stream_context_create();

        $socket = @stream_socket_client(
            "{$prefix}{$host}:{$port}",
            $errno,
            $errstr,
            15,
            STREAM_CLIENT_CONNECT,
            $ctx
        );

        if (!$socket) {
            Logger::error('mail', "SMTP 连接失败: {$errstr} ({$errno})");
            return false;
        }

        try {
            self::smtpRead($socket); // banner

            // EHLO
            self::smtpCmd($socket, 'EHLO ' . gethostname());
            self::smtpReadAll($socket);

            // STARTTLS
            if ($secure === 'tls') {
                self::smtpCmd($socket, 'STARTTLS');
                self::smtpRead($socket);
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                self::smtpCmd($socket, 'EHLO ' . gethostname());
                self::smtpReadAll($socket);
            }

            // AUTH LOGIN
            self::smtpCmd($socket, 'AUTH LOGIN');
            self::smtpRead($socket);
            self::smtpCmd($socket, base64_encode($user));
            self::smtpRead($socket);
            self::smtpCmd($socket, base64_encode($pass));
            self::smtpRead($socket);

            // MAIL FROM
            self::smtpCmd($socket, 'MAIL FROM:<' . $from . '>');
            self::smtpRead($socket);

            // RCPT TO
            self::smtpCmd($socket, 'RCPT TO:<' . $to . '>');
            self::smtpRead($socket);

            // DATA
            self::smtpCmd($socket, 'DATA');
            self::smtpRead($socket);

            $headers = "From: =?{$charset}?B?" . base64_encode($fromName) . "?= <{$from}>\r\n";
            $headers .= "To: {$to}\r\n";
            $headers .= "Subject: =?{$charset}?B?" . base64_encode($subject) . "?=\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset={$charset}\r\n";
            $headers .= "\r\n";

            fwrite($socket, $headers . $body . "\r\n.\r\n");
            self::smtpRead($socket);

            // QUIT
            self::smtpCmd($socket, 'QUIT');
        } finally {
            fclose($socket);
        }

        return true;
    }

    private static function smtpCmd($socket, string $cmd): void
    {
        fwrite($socket, $cmd . "\r\n");
    }

    private static function smtpRead($socket): string
    {
        $line = fgets($socket);
        if ($line === false || $line === '') {
            return '';
        }
        $code = (int) substr($line, 0, 3);
        if ($code >= 400) {
            throw new \RuntimeException('SMTP 错误: ' . trim($line));
        }
        return $line;
    }

    private static function smtpReadAll($socket): void
    {
        while (true) {
            $line = fgets($socket);
            if ($line === false || $line === '' || (isset($line[3]) && $line[3] === ' ')) {
                break;
            }
        }
    }
}
