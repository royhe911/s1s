<?php
namespace app\common\model\feigo;

use think\Model;

class Rotation extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];

    protected $table = 'feigo_rotation';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';
}
