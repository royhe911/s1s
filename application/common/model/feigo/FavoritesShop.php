<?php
namespace app\common\model\feigo;

use think\Model;

class FavoritesShop extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];

    protected $table = 'feigo_favorites_shop';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';

    /**
     * 返回用户的收藏门店id
     * @param $member_id
     * @return array
     */
    public static function getFavShopIdsByMember($member_id)
    {
        return self::where('member_id', $member_id)->order('create_time', 'desc')->column('shop_id');
    }
}
