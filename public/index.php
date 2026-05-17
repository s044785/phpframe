<?php
declare(strict_types=1);

// 框架入口文件，所有请求都从这里进入

use PHPFrame\App;

require __DIR__ . '/../src/bootstrap.php';

// 启动应用，执行路由分发
App::run();
