<!doctype html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title><?php echo htmlspecialchars((string)($title ?? 'PHPFrame'), ENT_QUOTES, 'UTF-8'); ?></title>
  <link href="/css/style.css" rel="stylesheet" />
</head>
<body>
  <div class="wrap">
    <div class="container">
      <?php echo $content ?? ''; ?>
    </div>
  </div>
</body>
</html>
