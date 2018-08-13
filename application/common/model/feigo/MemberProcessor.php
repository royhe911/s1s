<?php
namespace app\common\model\feigo;

use think\Model;

class MemberProcessor extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];

    protected $table = 'feigo_member_processor';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';
}
