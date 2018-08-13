<?php
namespace app\common\model\feigo;

use think\Model;

class ShopImportErr extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];

    protected $table = 'feigo_shop_import_err';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';
}