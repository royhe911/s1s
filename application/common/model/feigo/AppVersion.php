<?php
namespace app\common\model\feigo;

use think\Model;

class AppVersion extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';
}
