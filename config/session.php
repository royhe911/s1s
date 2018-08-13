<?php
// +----------------------------------------------------------------------
// | 会话设置
// +----------------------------------------------------------------------

return [
    'id'             => '',
    // SESSION_ID的提交变量,解决flash上传跨域
    'var_session_id' => '',
    // SESSION 前缀
    'prefix'         => 'qxg_',
    // 驱动方式 支持redis memcache memcached
    'type'           => 'redis',
    // 是否自动开启 SESSION
    'auto_start'     => true,
	// redis主机
    'host'           => '127.0.0.1',
	// redis端口
    'port'           => '6379',
	
	'path'=>'/tmp'
];
