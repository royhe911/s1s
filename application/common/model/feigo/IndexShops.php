<?php
namespace app\common\model\feigo;

use think\Model;

class IndexShops extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];

    protected $table = 'feigo_index_shops';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';

    /**
     * 门店分类关联门店
     * @return $this
     */
    public function shopTypeShop()
    {
        return $this->hasMany('shopTypeShop', 'shop_id', 'id')->field('type_id, shop_id');
    }

    /**
     * 智能排序 优先排推荐的
     * @param $region_id
     * @param $limit
     * @param string $order
     * @return string
     */
    public static function getIntelligenceOrder($region_id, $order = ' desc, distance asc, s.score desc')
    {
        $ids_arr = self::alias('is')
            ->join('shops s', 's.id=is.id')
            ->where(['s.status' => 1, 's.region_id_2' => $region_id])
            ->order('is.sort', 'desc')
            ->group('s.id')
            ->column('s.id');
        $order_str = '';
        if (!empty(implode(',', $ids_arr))) {
            $ids = 's.id, ' . implode(',', $ids_arr);
            $order_str = "field({$ids})" . $order;
        }
        return $order_str;
    }
}
