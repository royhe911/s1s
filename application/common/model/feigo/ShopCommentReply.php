<?php
namespace app\common\model\feigo;

use think\Model;

class ShopCommentReply extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];

    protected $table = 'feigo_shop_comment_reply';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';
}
