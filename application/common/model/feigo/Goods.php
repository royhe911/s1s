<?php
namespace app\common\model\feigo;

use think\Model;

class Goods extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];

    protected $table = 'feigo_goods';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';

    /**
     * 商品图片
     * @return $this
     */
    public function goodImages()
    {
        return $this->hasMany('GoodImages', 'good_id', 'id')->field('good_id, goods_image')->order('sort', 'asc');
    }

    /**
     * 商品详情图片
     * @return $this
     */
    public function goodDetailImages()
    {
        return $this->hasMany('GoodDetailImages', 'good_id', 'id')->field('good_id, detail_image')->order('sort', 'asc');
    }

    /**
     * 商品品牌
     * @return \think\model\relation\HasOne
     */
    public function goodBrand()
    {
        return $this->hasOne('Brands', 'id', 'brand_id');
    }

    /**
     * 首页推荐没有 随机取商品库数据
     * @param $region_id
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getRandGood($region_id)
    {
        $good_list = cache('index_good_list_' . $region_id);
        if (empty($good_list) || $good_list == false) {
            $city_brand_ids = BrandShop::getBranByCityShop($region_id);
            $good_list = (new Goods())->alias('g')
                ->join('brands b', 'b.id=g.brand_id')
                ->field('g.id, g.title, g.brand_id, g.price, g.preview_image, b.name')
                ->where("g.brand_id", "in", $city_brand_ids)
                ->where("g.status", 1)
                ->where("g.is_delete", 0)
                ->where("g.is_shop", 0)
                ->group("b.brand_id")
                ->limit(30)
                ->order("rand()")
                ->select()->toArray();
            cache('index_good_list_' . $region_id, $good_list, 86400);
        }
        return $good_list;
    }

    /**
     * 根据门店来源获取相应商品
     * @param $shop_info
     * @param $shop_id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getGoodByShopSource($shop_info, $shop_id, $limit = 6)
    {
        $w2 = [
            'g.status' => 1,
            'g.is_delete' => 0,
            'b.status' => 1
        ];
        if ($shop_info['data_source'] == 1) {
            $w2['g.is_shop'] = 0;
            $w2['g.brand_id'] = $shop_info['brand_id'];
            $shop_good = self::alias('g')
                ->join('brands b', 'b.id=g.brand_id')
                ->field('g.id, g.id as good_id, g.title, g.brand_id, g.price, g.preview_image, b.name')
                ->where($w2)->order('g.buy_count', 'desc')
                ->limit($limit)->select()->toArray();
        } else {
            $w2['g.is_shop'] = 1;
            $shop_good = self::alias('g')->where($w2)->where('g.id', 'in', function ($query) use ($shop_id) {
                $query->table('feigo_good_shop')->where('shop_id', $shop_id)->field('good_id');
            })->join('brands b', 'b.id=g.brand_id')
                ->field('g.id, g.id as good_id, g.title, g.brand_id, g.price, g.preview_image, b.name')
                ->order('g.buy_count', 'desc')
                ->limit($limit)->select()->toArray();
        }
        return $shop_good;
    }
}
