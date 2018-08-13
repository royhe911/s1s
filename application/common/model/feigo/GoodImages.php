<?php
namespace app\common\model\feigo;

use think\Model;

class GoodImages extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];

    protected $table = 'feigo_good_images';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';
}
