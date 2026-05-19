<?php
declare(strict_types=1);

namespace PHPFrame\Support;

// 邮件发送工具：优先使用 mail()，失败时写入日志文件（开发环境可用）
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
        $from = Env::get('MAIL_FROM', 'noreply@phpframe.local') ?? 'noreply@phpframe.local';
        $fromName = Env::get('MAIL_FROM_NAME', 'PHPFrame') ?? 'PHPFrame';

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8',
            'From: ' . $fromName . ' <' . $from . '>',
        ];

        // 尝试系统 mail()
        if (@mail($to, $subject, $body, implode("\r\n", $headers))) {
            return true;
        }

        // 开发环境：写入日志文件
        Logger::info('mail', "To: {$to} | Subject: {$subject}\n{$body}");
        return true;
    }
}
