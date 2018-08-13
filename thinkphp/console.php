<?php

namespace think;

// ThinkPHP 引导文件
// 加载基础文件
require __DIR__ . '/base.php';

// 执行应用
Container::get('app', [defined('APP_PATH') ? APP_PATH : ''])->initialize();
Console::init();
