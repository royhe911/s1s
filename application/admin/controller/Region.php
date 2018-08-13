<?php
namespace app\admin\controller;

use think\facade\Cache;

use app\common\controller\Backend;
use app\common\model\feigo\Config;
use app\common\model\feigo\Regions;
use app\common\model\feigo\Shops;

class Region extends Backend
{
    protected $relationSearch = true;
    protected $layout = 'default2';

    /**
     * 城市列表
     */
    public function index()
    {
        $is_super = $this->checkRegionAllAuth();

        if ($is_super) {
            $list = Regions::getTreeN(100000);
        } else {
            $list = Regions::getTreeN($this->city_id);
        }

        $support_city = implode(',', Config::getValue('support_city'));
        $hot_city = $is_super ? $this->hotList() : [];//获取热门城市

        $this->view->assign('support_city', $support_city);
        $this->view->assign('hot_city', $hot_city);
        $this->view->assign('is_super', $is_super);
        $this->view->assign('list', $list);
        return $this->view->fetch();
    }

    /**
     * 新增商圈
     * @return string
     */
    public function add()
    {
        $is_super = $this->checkRegionAllAuth();
        $region_id_3 = request()->param('region_id_3');

        if (!$is_super) {
            $region_id_2 = Regions::getPreId($region_id_3);
            if ($region_id_2['id'] != $this->city_id) $this->error(__('非法操作'));
        }

        if (request()->isAjax()) {
            $name = request()->param('name');
            $zuobiao = request()->param('zuobiao');
            if ($name == null || $region_id_3 == null || empty($zuobiao)) $this->error(__('请完善信息！'));
            if (strstr($zuobiao, ',') === false) $this->error(__('请完善信息！'));
            $zuobiao_arr = explode(',', $zuobiao);
            $lng = $zuobiao_arr[0];
            $lat = $zuobiao_arr[1];
            if (empty($lat) || empty($lng)) $this->error(__('请完善信息！'));

            $region_3 = Regions::get($region_id_3);
            if (empty($region_3)) $this->error(__('请求有误！'));

            $result = Regions::where('parent_id', $region_id_3)->field('id')->order('id desc')->find();

            if ($result){
                $id = $result['id'] + 1;
            }else{
                $id = $region_id_3 . '001';
            }

            $data = [
                'id' => $id,
                'name' => $name,
                'parent_id' => $region_id_3,
                'level_type' => 4,
                'lng' => $lng,
                'lat' => $lat,
                'city_code' => $region_3['city_code'],
                'zip_code' => $region_3['zip_code'],
                'merger_name' => $region_3['merger_name'] . ',' . $name,
            ];

            if (!Regions::create($data)) $this->error(__('Operation failed'));

            Cache::rm('region_all_tree');
            $this->success(__('Operation completed'), '', ['id' => $id]);
        }


        $this->view->assign('region_id_3', $region_id_3);
        return $this->view->fetch('edit');
    }

    /**
     * 编辑城市名称
     * @param null $ids
     */
    public function edit($ids = null)
    {
        if (request()->isAjax()) {
            $id = request()->param('id');
            $name = request()->param('name');
            $zuobiao = request()->param('zuobiao');

            $is_super = $this->checkRegionAllAuth();
            if (!$is_super) {
                $data = Regions::where('id', $id)->field('parent_id')->find();
                $region_id_2 = Regions::getPreId($data['parent_id']);
                if ($region_id_2 != $this->city_id) $this->error(__('非法操作'));
            }

            if (strstr($zuobiao, ',') === false) $this->error(__('请完善信息！'));
            $zuobiao_arr = explode(',', $zuobiao);
            $lng = $zuobiao_arr[0];
            $lat = $zuobiao_arr[1];
            if (empty($lat) || empty($lng)) $this->error(__('请完善信息！'));

            if (empty($id)) $this->error(__('请完善信息！'));

            $data = [
                'name' => $name,
                'lng' => $lng,
                'lat' => $lat
            ];

            if (Regions::where('id', $id)->update($data) === false) $this->error(__('Operation failed'));

            Cache::rm('region_all_tree');
            $this->success(__('Operation completed'));
        }
        $row = Regions::get($ids);
        $this->view->assign('row', $row);
        return $this->view->fetch('edit');
    }

    /**
     * 编辑市状态
     */
    public function edit_status()
    {
        $is_super = $this->checkRegionAllAuth();
        if (!$is_super) $this->error(__('非法操作'));

        $region_id_2 = request()->post('ids');
        $status = request()->post('status');

        if (empty($region_id_2) || $status == null) $this->error(__('Parameter exception'));

        $info = Regions::where('id', $region_id_2)->where('level_type', 2)->find();
        if (empty($info)) $this->error(__('该选择要' . $status == 1 ? '开通' : '禁用' . '的城市'));

        $support_city = Config::getValue('support_city');

        if ($status == 1) {
            if (in_array($region_id_2, $support_city)) $this->error(__('该城市已开通'));
            array_push($support_city, $region_id_2);
        } else {
            if (!in_array($region_id_2, $support_city)) $this->error(__('该城市已禁用'));

            $result = Config::where('c_key', 'hot_city')->field('c_value')->find();
            if (strstr($result['c_value'], $region_id_2) !== false) $this->error(__('该城市为热门城市不可禁用'));

            $key = array_search($region_id_2, $support_city);
            unset($support_city[$key]);
        }

        sort($support_city);

        if (!Config::where('c_key', 'support_city')->setField('c_value', json_encode($support_city))) $this->error(__('Operation failed'));

        Cache::rm('config_value_support_city');
        $this->success(__('Operation completed'));
    }

    /**
     * 删除城市
     */
    public function delete()
    {
        $ids = request()->post('ids');

        $is_super = $this->checkRegionAllAuth();
        if (!$is_super) {
            $data = Regions::where('id', $ids)->field('parent_id')->find();
            $region_id_2 = Regions::getPreId($data['parent_id']);
            if ($region_id_2 != $this->city_id) $this->error(__('非法操作'));
        }

        if (empty($ids)) $this->error(__('Parameter exception'));

        $region_4 = Regions::get($ids);

        if (empty($region_4) || $region_4['level_type'] != 4) $this->error(__('请求有误'));

        if (Shops::where('region_id_4', $ids)->find()) $this->error(__('有门店关联，不能删除'));

        if (!Regions::where('id', $ids)->delete()) $this->error(__('Operation failed'));

        Cache::rm('region_all_tree');
        $this->success(__('Operation completed'));
    }

    /**
     * 热门市列表
     * @Author: zhuhaijun
     * @Date: 2018/4/19
     */
    private function hotList()
    {
        $hot_city = Regions::whereIn('id', Config::getValue('hot_city'))->field('id, name')->select()->toArray();

        return $hot_city;
    }

    /**
     * 新增热门市
     * @Author: zhuhaijun
     * @Date: 2018/4/19
     */
    public function hot_add()
    {
        $is_super = $this->checkRegionAllAuth();
        if (!$is_super) $this->error(__('非法操作'));

        $region_id_2 = request()->post('ids');
        if (empty($region_id_2)) $this->error(__('Parameter exception'));

        $info = Regions::where('id', $region_id_2)->where('level_type', 2)->find();
        if (empty($info)) $this->error(__('该选择正确的城市'));

        $result = Config::where('c_key', 'support_city')->field('c_value')->find();
        if (strstr($result['c_value'], $region_id_2) === false) $this->error(__('该城市暂未开通'));

        $result = Config::where('c_key', 'hot_city')->field('c_value')->find();
        if (strstr($result['c_value'], $region_id_2) !== false) $this->success(__('该城市已为热门城市'));

        $hot_city_list = json_decode($result['c_value'], true);
        array_push($hot_city_list, $region_id_2);

        if (!Config::where('c_key', 'hot_city')->setField('c_value', json_encode($hot_city_list))) $this->error(__('Operation failed'));

        $hotCityData = $this->hotList($hot_city_list);//获取热门城市
        Cache::rm('config_value_hot_city');
        $this->success(__('Operation completed'), '', $hotCityData);
    }

    /**
     * 编辑热门市排序
     * @Author: zhuhaijun
     * @Date: 2018/4/19
     */
    public function hot_sort()
    {
        $is_super = $this->checkRegionAllAuth();

        if (!$is_super) $this->error(__('非法操作'));

        $jsonStr = $_REQUEST['data'];

        if (empty($jsonStr)) $this->error(__('Parameter exception'));
        if (is_string($jsonStr)) {
            $data = json_decode($jsonStr, true);
        } else {
            $data = $jsonStr;
        }

        if (!is_array($data)) $this->error(__('Parameter exception'));

        $result = Config::where('c_key', 'hot_city')->field('c_value')->find();

        $hot_city_list = json_decode($result['c_value'], true);

        $ids = array_column($data, 'id');
        $dataJsonStr = json_encode($ids);

        sort($ids);
        sort($hot_city_list);

        $bool = array_diff_assoc($hot_city_list, $ids);
        if ($bool) $this->error(__('数据前后不一致'));

        if (!Config::where('c_key', 'hot_city')->setField('c_value', $dataJsonStr)) $this->error(__('Operation failed'));

        Cache::rm('config_value_hot_city');
        $this->success(__('Operation completed'));
    }

    /**
     * 删除热门市
     * @Author: zhuhaijun
     * @Date: 2018/4/19
     */
    public function hot_delete()
    {
        $is_super = $this->checkRegionAllAuth();
        if (!$is_super) $this->error(__('非法操作'));

        $region_id_2 = request()->post('ids');
        if (empty($region_id_2)) $this->error(__('Parameter exception'));


        $info = Regions::where('id', $region_id_2)->where('level_type', 2)->find();

        if (empty($info)) $this->error(__('该选择正确的城市'));


        $result = Config::where('c_key', 'hot_city')->field('c_value')->find();
        if (strstr($result['c_value'], $region_id_2) === false) $this->success(__('热门城市中不存在该城市'));


        $hot_city_list = json_decode($result['c_value'], true);
        $key = array_search($region_id_2, $hot_city_list);
        unset($hot_city_list[$key]);

        sort($hot_city_list);


        if (!Config::where('c_key', 'hot_city')->setField('c_value', json_encode($hot_city_list))) $this->error(__('Operation failed'));

        $hotCityData = $this->hotList($hot_city_list);//获取热门城市
        Cache::rm('config_value_hot_city');
        $this->success(__('Operation completed'), '', $hotCityData);
    }

    /**
     * 根据经纬度判断城市是否开通
     * @return array
     */
    public function check_city_info_lal()
    {
        $lat = request()->post('lat');
        $lng = request()->post('lng');
        if (empty($lng) || empty($lat)) {
            return $this->result(null, 0, '没有经纬度');
        }
        $result_data = Regions::getCityinfo($lat, $lng);
        if($result_data['code'] != '1'){
            return $this->result(null, 0, $result_data['msg']);
         }

        $this->result($result_data['data']['region_data'], 1);
    }

    /*
    * 首页选择地区弹窗
    */
    public function region_window()
    {
        return $this->view->fetch();
    }
}
