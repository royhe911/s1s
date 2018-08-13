<?php
namespace app\common\model\feigo;

use think\Model;

class ShopTypes extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];

    protected $table = 'feigo_shop_types';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';

    /**
     * 获取所有分类
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getAllType()
    {
        $list = cache("shop_types_all");
        if (empty($list)) {
            $list = self::where('is_show', 1)->order("sort asc")->hidden('is_show')->select()->toArray();
            cache("shop_types_all", $list);
        }
        return $list;
    }

    /**
     * 获取所有分类 id => 值
     * @return array|mixed
     */
    public static function getAllTypeToS()
    {
        $list = cache("shop_types_all_s");
        if (empty($list)) {
            $list = self::where('is_show', 1)->order("sort asc")->column('name', 'id');
            cache("shop_types_all_s", $list);
        }
        return $list;
    }
}
