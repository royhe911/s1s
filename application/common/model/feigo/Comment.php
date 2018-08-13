<?php
namespace app\common\model\feigo;

use think\Model;

class Comment extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];

    protected $table = 'feigo_comment';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';
}
