<?php
namespace app\common\model\feigo;

use think\Model;

class BrandGoodtype extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];
    
    protected $table = 'feigo_brand_good_type';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';
}
