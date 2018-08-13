<?php
namespace app\common\model\feigo;

use think\Model;

class BusinessMessage extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];

    protected $table = 'feigo_business_message';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';
}
