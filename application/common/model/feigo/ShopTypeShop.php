<?php
namespace app\common\model\feigo;

use think\Model;

class ShopTypeShop extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];

    protected $table = 'feigo_shop_type_shop';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';
}