<?php
declare(strict_types=1);

namespace PHPFrame\Support;

// 图片验证码：生成随机字符 → 绘制带干扰线的 PNG 图片
final class Captcha
{
    // 生成验证码文本（4 位，数字+大写字母，去除易混淆字符）
    public static function generate(): string
    {
        $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $code = '';
        for ($i = 0; $i < 4; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $code;
    }

    // 输出 PNG 验证码图片
    public static function output(string $code): void
    {
        $width = 120;
        $height = 44;

        $img = imagecreatetruecolor($width, $height);
        if ($img === false) {
            throw new \RuntimeException('GD 库不可用');
        }

        // 背景颜色
        $bg = imagecolorallocate($img, 240, 240, 245);
        imagefill($img, 0, 0, $bg);

        // 干扰线（3 条）
        for ($i = 0; $i < 3; $i++) {
            $lineColor = imagecolorallocate($img,
                random_int(160, 210),
                random_int(160, 210),
                random_int(180, 220)
            );
            imageline($img,
                random_int(0, $width),
                random_int(0, $height),
                random_int(0, $width),
                random_int(0, $height),
                $lineColor
            );
        }

        // 干扰点
        for ($i = 0; $i < 80; $i++) {
            $dotColor = imagecolorallocate($img,
                random_int(180, 220),
                random_int(180, 220),
                random_int(200, 230)
            );
            imagesetpixel($img,
                random_int(0, $width),
                random_int(0, $height),
                $dotColor
            );
        }

        // 逐个字符绘制（不同颜色、随机偏移）
        $len = strlen($code);
        for ($i = 0; $i < $len; $i++) {
            $charColor = imagecolorallocate($img,
                random_int(30, 100),
                random_int(40, 120),
                random_int(80, 160)
            );
            $fontSize = 5; // GD 内置字体 5 号
            $x = 12 + $i * 25 + random_int(-4, 4);
            $y = random_int(8, 14);
            imagestring($img, $fontSize, $x, $y, $code[$i], $charColor);
        }

        ob_start();
        imagepng($img);
        imagedestroy($img);
        $data = (string) ob_get_clean();

        header('Content-Type: image/png');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo $data;
    }
}
