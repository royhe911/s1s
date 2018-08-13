<?php
namespace app\common\model\feigo;

use think\Model;

class MemberShopHistory extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];

    protected $table = 'feigo_member_shop_history';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';
}
