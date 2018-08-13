<?php
namespace app\common\model\feigo;

use think\Model;

class IndexGroupShopType extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];

    protected $table = 'feigo_index_group_shop_type';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';
}
