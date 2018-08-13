<?php
namespace app\common\model\feigo;

use think\Model;
use app\common\model\feigo\Config;

class Regions extends Model
{
    protected $connection = [
        'database' => 'feigo',
    ];

    protected $table = 'feigo_regions';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';

    /**
     * 获取所有数据
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getAll()
    {
        $list = cache('region_all');
        if (empty($tree)) {
            $list = self::select()->toArray();
            cache('region_all', $list);
        }
        return $list;
    }

    /**
     * 获取热门城市列表
     * @Author: zhuhaijun
     * @Date: 2018/4/27
     */
    public static function getHotCityList()
    {
        $hotCityIdArr = Config::getValue('hot_city');
        $result = self::where("id", "in", $hotCityIdArr)->group("id")->select();
        return empty($result) ? null : $result;
    }

    /**
     * 获取已开通城市
     * @return array|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getSupportCity()
    {
        $support_city = self::where("level_type", 2)
            ->where('id', 'in', Config::getValue('support_city'))
            ->select();
        return $support_city;
    }

    /**
     * 根据经纬度判断城市是否开通
     * @param $latitude
     * @param $longitude
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getCityinfoBylal($latitude, $longitude)
    {
        // 获取请求接口的参数
        $api_config = config("other_api.baiduapi_lal");
        $ak = $api_config['appKey'];
        $url = $api_config['appUrl'];

        $lal = $latitude . ',' . $longitude;
        $url = $url . '/?location=' . $lal . '&output=json&pois=1&ak=' . $ak;
        $city_info = file_get_contents($url);
        $city_info = json_decode($city_info, true);
        if ($city_info['status'] != 0 || !isset($city_info['result']['addressComponent']['adcode'])) {
            return ['status' => 2];
        }
        $adcode = $city_info['result']['addressComponent']['adcode'];
        $regions = Regions::where("id", $adcode)->find();
        $city_info_result['city_id'] = $adcode;
        if (empty($regions)) {
            return ['status' => 2];
        }
        if ($regions['level_type'] == 3) $adcode = $regions['parent_id'];
        $city_info_result['city_id'] = $adcode;
        if (!in_array($adcode, Config::getValue('support_city'))) {
            return ['status' => 3];
        }
        $city_name = Regions::where('id', $adcode)->value('name');
        return ['status' => 1, 'city_id' => $adcode, 'city_name' => $city_name, 'city_info' => $city_info];
    }

    /**
     * 城市多级
     * @param $region_id
     * @return array|mixed|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getTree($region_id)
    {
        $region_tree_list = cache('region_tree_list_' . $region_id);
        if (empty($region_tree_list) || $region_tree_list == false) {
            // 查3级
            $region = self::where('parent_id', $region_id)->select()->toArray();
            if (!empty($region)) {
                $region_ids = [];
                foreach ($region as $v) {
                    $region_ids[] = $v['id'];
                }
                $region_tree_list = self::where('parent_id', 'in', $region_ids)->select()->toArray();
                if (!empty($region_tree_list)) {
                    foreach ($region as $k => $v) {
                        $vv2 = [];
                        foreach ($region_tree_list as $k2 => $v2) {
                            if ($v['id'] == $v2['parent_id']) {
                                $vv2[] = $v2;
                            }
                        }
                        $region[$k]['child'] = $vv2;
                    }
                }
            }
            $region_tree_list = $region;
            cache('region_tree_list_' . $region_id, $region_tree_list);
        }
        return $region_tree_list;
    }

    /**
     * @param bool $refresh 是否刷新
     * @return mixed
     */
    public static function getTreeAll($refresh = false)
    {
        $tree = cache('region_all_tree');

        if ($refresh || empty($tree)) {
            $tree = [];
            $dataAll = self::getAll();
            if (!empty($dataAll)) {
                foreach ($dataAll as &$value) $tree[$value['parent_id']][$value['id']] = $value;
                cache('region_all_tree', $tree);
            }
        }
        return $tree;
    }

    /**
     * 获取n层结构
     * @param int $pid
     * @param null $treeAll
     * @param string $childName
     * @return null
     */
    public static function getTreeN($pid = 0, $treeAll = null, $childName = 'child')
    {
        if (empty($treeAll)) $treeAll = self::getTreeAll();
        if (empty($treeAll[$pid])) return null;

        foreach ($treeAll[$pid] as &$value) $value[$childName] = self::getTreeN($value['id'], $treeAll, $childName);

        return $treeAll[$pid];
    }

    /**
     * 返回上一级的id
     * @param $region_id_3
     * @return mixed
     */
    public static function getPreId($region_id_3)
    {
        return Regions::where('id', '=', function ($query) use ($region_id_3) {
            $query->name("regions")->where('id', $region_id_3)->field('parent_id')->find();
        })->value('id');
    }

    public static function getCityIdByIp(){
        // 获取请求接口的参数
        $api_config = config("other_api.baiduapi_ip");
        $ak = $api_config['appKey'];
        $url = $api_config['appUrl'];

        $url = $url . '?ak=' . $ak . '&ip='.getIp(); //'183.15.240.47'
        $city_info = file_get_contents($url);
        $city_info = json_decode($city_info, true);
        if ($city_info['status'] != 0) {
            return 0;
        }
        $region_info = Regions::where('name','like', $city_info['content']['address_detail']['city'])->field('id as region_id_2,name')->find();
        if($region_info){
            return  $region_info['region_id_2'];
        }
        return 0;
    }

    /**
     * 根据经纬度获取省市区商圈
     * @param $latitude
     * @param $longitude
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getCityinfo($latitude, $longitude)
    {
        $result_data = self::getCityinfoBylal($latitude, $longitude);
        if ($result_data['status'] != 1) {
            $result['code'] = '30002';
            $result['msg'] = lang($result['code']);
            $result['data'] = null;
            return $result;
        }
        $city_list = Config::getValue('support_city');
        if (array_search($result_data['city_id'], $city_list) === false) {
            $result['code'] = '30004';
            $result['msg'] = lang($result['code']);
            $result['data'] = null;
            return $result;
        }
        if (!isset($result_data['city_info'])) {
            $result['code'] = '30002';
            $result['msg'] = lang($result['code']);
            $result['data'] = null;
            return $result;
        }
        $city_info = $result_data['city_info'];
        if (!isset($city_info['result']['formatted_address'])) {
            $result['code'] = '30002';
            $result['msg'] = lang($result['code']);
            $result['data'] = null;
            return $result;
        }
        $address = $city_info['result']['formatted_address'];

        if (!isset($city_info['result']['addressComponent']['adcode'])) {
            $result['code'] = '30002';
            $result['msg'] = lang($result['code']);
            $result['data'] = null;
            return $result;
        }
        $adcode = $city_info['result']['addressComponent']['adcode'];
        if (!$region_info_3 = Regions::get($adcode)) {
            $result['code'] = '30004';
            $result['msg'] = lang($result['code']);
            $result['data'] = null;
            return $result;
        }

        $region_id_1 = Regions::where('id',$result_data['city_id'])->value('parent_id');
        $region_info_1 = Regions::where('id',$region_id_1)->field('id as region_id_1,name')->find();
        $region_info_2 = Regions::where('id',$result_data['city_id'])->field('id as region_id_2,name')->find();
        $region_info_3 = Regions::where('id',$city_info['result']['addressComponent']['adcode'])->field('id as region_id_3,name')->find();
        $region_list_4 = Regions::where('parent_id', $city_info['result']['addressComponent']['adcode'])->where('level_type', 4)->field('id,name')->select();

        $result_data = [
            'address' => $city_info['result']['formatted_address'],
            'region_info_1' => $region_info_1,
            'region_info_2' => $region_info_2,
            'region_info_3' => $region_info_3,
            'region_list_4' => $region_list_4
        ];
        $result['code'] = '1';
        $result['data']['region_data'] = $result_data;
        $result['msg'] = lang($result['code']);
        return $result;
    }

}
