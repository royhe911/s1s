<?php
namespace app\common\model\feigo;

use think\Exception;
use think\Model;

class Shops extends Model
{
    protected $connection = [
        'database' => 'feigo',
    ];
    protected $table = 'feigo_shops';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';

    /**
     * 门店分类中间表
     * @return $this
     */
    public function shopTypeShop()
    {
        return $this->hasMany('shopTypeShop', 'shop_id', 'id')->field('type_id, shop_id');
    }

    /**
     * 门店相册
     * @return $this
     */
    public function shopAlbum()
    {
        return $this->hasMany('shopAlbum', 'shop_id', 'id')->field('image_url, shop_id')->order('id', 'asc');
    }

     /**
     * 省级
     * @return \think\model\relation\HasMany
     */
    public function region1()
    {
        return $this->hasMany('Regions', 'id', 'region_id_1');
    }

    /**
     * 市级
     * @return \think\model\relation\HasMany
     */
    public function region2()
    {
        return $this->hasMany('Regions', 'id', 'region_id_2');
    }

    /**
     * 区级
     * @return \think\model\relation\HasMany
     */
    public function region3()
    {
        return $this->hasMany('Regions', 'id', 'region_id_3');
    }

    /**
     * 商圈级
     * @return \think\model\relation\HasMany
     */
    public function region4()
    {
        return $this->hasMany('Regions', 'id', 'region_id_4');
    }

    /**
     * 省级
     * @return \think\model\relation\HasMany
     */
    public function region_1()
    {
        return $this->hasOne('Regions', 'id', 'region_id_1')->field("id,name");
    }

    /**
     * 区级
     * @return \think\model\relation\HasMany
     */
    public function region_2()
    {
        return $this->hasOne('Regions', 'id', 'region_id_2')->field("id,name");
    }
    

    /**
     * 区级
     * @return \think\model\relation\HasMany
     */
    public function region_3()
    {
        return $this->hasOne('Regions', 'id', 'region_id_3')->field("id,name");
    }
    
    /**
     * 商圈级
     * @return \think\model\relation\HasMany
     */
    public function region_4()
    {
        return $this->hasOne('Regions', 'id', 'region_id_4')->field("id,name");
    }

    /**
     * 获取商品的门店
     * @param $goods_info
     * @param $longitude
     * @param $latitude
     * @param $region_id
     * @param $default_limit
     * @param int $page
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getGoodShops($goods_info, $longitude, $latitude, $region_id, $default_limit, $page = 1)
    {
        $goods_id = $goods_info->id;
        $order_str = "s.id desc";
        $distance_field = ",0 as distance";
        if (!empty($longitude) && !empty($latitude)) {
            // $order_str = "((latitude-{$latitude})*(latitude-{$latitude})+(longitude-{$longitude})*(longitude-{$longitude}))";
            $order_str = "distance asc,s.id desc";
            $distance_field = ",(st_distance (point (s.longitude,s.latitude),point({$longitude},{$latitude}) ) * 111195) as distance";
        }

        if ($goods_info['is_shop'] == 1) {
            $shops = $this->alias('s')->with('shopTypeShop')
                ->join('shop_type_shop sts', 'sts.shop_id=s.id')
                ->join('regions r', 'r.id=s.region_id_4','left')
                ->field("s.id,s.name,s.score,s.preview_image,s.address,s.phone_number,s.phone_number1,s.longitude,s.latitude,r.name as region_name" . $distance_field)
                ->where("s.id", "in", function ($query) use ($goods_id) {
                    $query->name('good_shop')
                        ->field("shop_id")
                        ->where("good_id", $goods_id)
                        ->select();
                })
                ->limit($default_limit)
                ->page($page)
                ->order($order_str)
                ->group("s.id")
                ->select();

            $shop_count = $this->alias('s')
                ->join('shop_type_shop sts', 'sts.shop_id=s.id')
                ->join('regions r', 'r.id=s.region_id_4','left')
                ->where("s.status", 1)
                ->where("s.id", "in", function ($query) use ($goods_id) {
                    $query->name('good_shop')
                        ->field("shop_id")
                        ->where("good_id", $goods_id)
                        ->select();
                })
                ->group("s.id")
                ->count();
        } else {
            $brand_id = $goods_info->brand_id;
            $shops = $this->alias('s')
                ->with('shopTypeShop')
                ->join('shop_type_shop sts', 'sts.shop_id=s.id')
                ->join('regions r', 'r.id=s.region_id_4', "left")
                ->field("s.id,s.name,s.score,s.preview_image,s.address,s.phone_number,s.phone_number1,s.longitude,s.latitude,r.name as region_name" . $distance_field)
                ->where("s.id", "in", function ($query) use ($brand_id) {
                    $query->name('brand_shop')
                        ->field("shop_id")
                        ->where("brand_id", $brand_id)
                        ->select();
                })
                ->where("s.status", 1)
                ->where("s.region_id_2", $region_id)
                ->limit($default_limit)
                ->page($page)
                ->order($order_str)
                ->group("s.id")
                ->select();

            $shop_count = $this->alias('s')
                ->join('shop_type_shop sts', 'sts.shop_id=s.id')
                ->join('regions r', 'r.id=s.region_id_4', "left")
                ->where("s.id", "in", function ($query) use ($brand_id) {
                    $query->name('brand_shop')
                        ->field("shop_id")
                        ->where("brand_id", $brand_id)
                        ->select();
                })
                ->where("s.status", 1)
                ->where("s.region_id_2", $region_id)
                ->group("s.id")
                ->count();
        }

        $result_data['shops_count'] = $shop_count;
        $result_data['page_count'] = ceil($shop_count / $default_limit);
        $result_data['shops'] = $shops;
        if (!empty($result_data['shops'])) {
            foreach ($result_data['shops'] as $k => $v) {
                $result_data['shops'][$k]['phone_number'] = filterShopPhone($v['phone_number']);
                $result_data['shops'][$k]['phone_number1'] = filterShopPhone($v['phone_number1']);
            }
        }
        return $result_data;
    }

    /**
     * 获取门店列表
     * @param $w
     * @param $order
     * @param $limit
     * @param $field
     * @param int $page
     * @param string $having
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getShopList($w, $order, $limit, $field, $page = 1, $having = '')
    {
        $shops = self::alias('s')
            ->with('shopTypeShop')
            ->join('shop_type_shop sts', 'sts.shop_id=s.id')
            ->join('regions r', 'r.id=s.region_id_4', 'left')
            ->field($field)->where($w)->order($order)->limit($limit)->page($page)
            ->having($having)->group('s.id')->select()->toArray();
        return $shops;
    }

    /**
     * 获取门店列表的总数
     * @param $w
     * @return int|string
     */
    public static function getShopListCount($w, $field = '', $having = '')
    {
        $shops_count = self::alias('s')
            ->join('shop_type_shop sts', 'sts.shop_id=s.id')
            ->join('regions r', 'r.id=s.region_id_4', 'left')
            ->field($field)
            ->where($w)
            ->group('s.id')
            ->having($having)
            ->count();
        return $shops_count;
    }

    /**
     * 简单搜索门店附带其他信息
     * @param $where
     * @param int $limit
     * @param int $page
     * @return array|bool
     * @Author: zhuhaijun
     * @Date: 2018/4/18
     */
    public function search($shop_name, $city_id = null, $limit = 10, $page = 1)
    {
        $where = [];
        if (!empty($city_id)) $where[] = ['region_id_2', '=', $city_id];
        $where[] = ['s.name|s.brand', 'like', "%$shop_name%"];
        $where[] = ['s.status', '=', 1];
        $where[] = ['s.is_delete', '=', 0];
//        $where[] = ['b.status', '=', 1];

        $have_shop_type = $have_goods_img = true;//相关配置

        try {
            $key = 's.id';
            $field = 's.id,s.name,s.score,s.preview_image,s.longitude,s.latitude';

            //查询出的门店总数
            $count = self::alias('s')
//                 ->join('brand_shop bs', 'bs.shop_id=s.id', 'left')
//                 ->join('brands b', 'b.id=bs.brand_id', 'left')
                ->where($where)
                ->field($key)
                ->group($key)
                ->count();

            //查询出的门店数据
            $data = self::alias('s')
//                 ->join('brand_shop bs', 'bs.shop_id=s.id', 'left')
//                 ->join('brands b', 'b.id=bs.brand_id', 'left')
                ->where($where)
                ->limit($limit)
                ->page($page)
                ->field($field)
                ->group($key)
                ->column($field, $key);

            //门店id数组
            $shop_id_arr = array_column($data, 'id');

            //加入门店下的商品图片
            if ($have_goods_img) {
                $countLimit = 3;

                $_where = [];
                $_where[] = ['a.shop_id', 'in', $shop_id_arr];
                $_where[] = ['b.status', '=', 1];
                $_where[] = ['b.is_delete', '=', 0];
                $_db = new GoodShop();
                $resultList = $_db->alias('a')
                    ->where($_where)
                    ->join('goods b', 'b.id=a.good_id')
                    ->field('b.preview_image,a.shop_id,a.good_id,b.price')
                    ->select();

                if ($resultList) foreach ($resultList as $value) {
                    $_arr = &$data[$value['shop_id']]['goods_img'];
                    if (isset($_arr) && count($_arr) >= $countLimit) continue;

                    $_arr[] = [
                        'id' => $value['good_id'],
                        'img' => $value['preview_image'],
                        'price' => $value['price']
                    ];
                }
            }

            //加入门店分类
            if ($have_shop_type) {
                $countLimit = 5;

                $_where = [];
                $_where[] = ['b.shop_id', 'in', $shop_id_arr];
                $_db = new ShopTypes();
                $resultList = $_db->alias('a')
                    ->where($_where)
                    ->join('shop_type_shop b', 'b.type_id=a.id')
                    ->field('b.shop_id,a.name')
                    ->select();

                if ($resultList) foreach ($resultList as $value) {
                    $_arr = &$data[$value['shop_id']]['type_name'];
                    if (isset($_arr) && count($_arr) >= $countLimit) continue;

                    $_arr[] = $value['name'];
                }
            }

            //重新排序
            $shop_list = [];
            foreach ($data as &$value) {
                if (!isset($value['type_name'])) $value['type_name'] = null;
                if (!isset($value['goods_img'])) $value['goods_img'] = null;
                $shop_list[] = $value;
            }

            return ['shop_list' => $shop_list, 'page_count' => ceil($count / $limit), 'page' => $page];

        } catch (Exception $e) {
            return false;
        }
    }

    /**

     * 校验数据是否为空
     * @param $shop_info
     * @return bool
     */
    public static function checkShopData($shop_info)
    {
        $un_empty_key_arr = ['name', 'address', 'contacts', 'region_id_1', 'region_id_2', 'region_id_3', 'longitude', 'latitude', 'type_ids', 'preview_image', 'preview_images'];

        foreach ($un_empty_key_arr as $value) {
            if (empty($shop_info[$value])) return false;
        }
        return true;
    }

    /*
     * 搜索门店(PC)
     * @param $w
     * @param $limit
     * @param int $page
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function searchNew($w, $limit, $page = 1)
    {
        //查询出的门店总数
        $count = self::alias('s')
            //->join('brand_shop bs', 'bs.shop_id=s.id', 'left')
            //->join('brands b', 'b.id=bs.brand_id', 'left')
            ->join('shop_type_shop sts', 'sts.shop_id=s.id')
            ->where($w)
            ->group('s.id')
            ->count();

        //查询出的门店数据
        $data = self::alias('s')
            ->with('shopTypeShop')
            //->join('brand_shop bs', 'bs.shop_id=s.id', 'left')
            //->join('brands b', 'b.id=bs.brand_id', 'left')
            ->join('shop_type_shop sts', 'sts.shop_id=s.id')
            ->where($w)
            ->field('s.id, s.id as shop_id, s.name, s.score, s.preview_image, s.phone_number, s.phone_number1, s.comment_count, s.address, s.longitude, s.latitude')
            ->limit($limit)->page($page)
            ->group('s.id')
            ->select()->toArray();
            //->column('s.id, s.id as shop_id, s.name, s.score, s.preview_image, s.longitude, s.latitude', 's.id');
        $shop_ids = array_column($data, 'id');

        $w = [];
        $w[] = ['gs.shop_id', 'in', $shop_ids];
        $w[] = ['g.status', '=', 1];
        $w[] = ['g.is_delete', '=', 0];
        $good_list = GoodShop::alias('gs')
            ->where($w)
            ->join('goods g', 'g.id=gs.good_id')
            ->field('g.preview_image, gs.shop_id, gs.good_id, gs.shop_id, g.price, g.title')
            ->limit(6)
            ->select()->toArray();

        foreach ($data as &$v) {
            $v['good_list'] = [];
            $v['phone_number'] = filterShopPhone($v['phone_number']);
            $v['phone_number1'] = filterShopPhone($v['phone_number1']);
            foreach ($good_list as $value) {
                if ($v['shop_id'] == $value['shop_id']) {
                    $v['good_list'][] = [
                        'good_id' => $value['good_id'],
                        'preview_image' => $value['preview_image'],
                        'price' => $value['price'],
                        'title' => $value['title']
                    ];
                }
            }
        }
        return ['shop_list' => $data, 'page_count' => ceil($count / $limit)];
    }

    /**
     * 处理营业时间,week加上key
     * @param $shop_info
     * @return bool
     */
    public static function formaBusinessHours($business_hours)
    {
        if(empty($business_hours)){
            return "";
        }
        $business_hours = json_decode($business_hours,true);
        if(!is_array($business_hours)){
            return "";
        }
        foreach($business_hours as $k=>$v){
            $weeks = [];
            foreach ($v['week'] as $v2) {
                if($v2) $weeks[$v2] = $v2;
            }
            $business_hours[$k] = ['week'=>$weeks,'time'=>$v['time']];
        }
        foreach ($business_hours as $k=>$v){
            if(empty($v['week']) && empty($v['time'])){
                 unset($business_hours[$k]);
            }
        }
        if(empty($business_hours)){
            return "";
        }else{
            return json_encode(array_values($business_hours));
        }

    }

    /**
     * 处理营业时间 week去掉key
     * @param $shop_info
     * @return bool
     */
    public static function handleBusinessHours($business_hours){
        if(empty($business_hours)){
            return "";
        }
        $business_hours = json_decode($business_hours,true);
        if(!is_array($business_hours)){
            return "";
        }
        foreach($business_hours as $k=>$v){
            $weeks = [];
            foreach ($v['week'] as $v2) {
                if($v2) $weeks[] = $v2;
            }
            $business_hours[$k] = ['week'=>$weeks,'time'=>$v['time']];
        }
        return json_encode($business_hours);
    }
}
