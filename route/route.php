<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
    '__domain__'=>[
        '' => 'index',
        \think\facade\Config::get('url_prefix_www') => 'index',
        \think\facade\Config::get('url_prefix_api') => 'api',
        \think\facade\Config::get('url_prefix_admin') => 'admin',
        \think\facade\Config::get('url_prefix_merchant') => 'merchant',
        ],
    '__pattern__' => [
        'name' => '\w+',
    ],
];
