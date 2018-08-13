<?php
namespace app\common\model\feigo;

use think\Model;

class BrandShop extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];

    protected $table = 'feigo_brand_shop';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';

    /**
     * 根据城市门店获取当前城市的品牌
     * @param $region_id
     * @return array|mixed
     */
    public static function getBranByCityShop($region_id)
    {
        $city_brand_ids = cache("city_brand_ids_{$region_id}");
        if (empty($city_brand_ids) || $city_brand_ids == false) {
            $city_brand_ids = self::alias('bs')
                ->join('brands b', 'b.id=bs.brand_id')
                ->where('b.status', 1)
                ->field("bs.brand_id")->where("bs.shop_id", "in", function ($query) use ($region_id) {
                $query->name("shops")
                    ->alias('ss')
                    ->join('shop_type_shop sts', 'sts.shop_id=ss.id')
                    ->field("ss.id")
                    ->where("ss.region_id_2", $region_id)
                    ->where("ss.status", 1)
                    ->where("ss.is_delete", 0)
                    ->select();
                })
                ->group("bs.brand_id")
                ->column('bs.brand_id');
            cache("city_brand_ids_{$region_id}", $city_brand_ids);
        }
        return $city_brand_ids;
    }
}
