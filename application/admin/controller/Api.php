<?php
namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\model\feigo\Regions;

class Api extends Backend
{
    public function get_token()
    {
        return $this->result(\Qiniu::token(3600, config('qiniu.bucket')), 0, '', 'json');
    }

    /**
     * 获取后台的地区列表
     * @Author: zhuhaijun
     * @Date: 2018/4/27
     */
    public function get_city_list()
    {
        $city_id = array_keys($this->city_list);
        $city_list = Regions::where('id', 'in', $city_id)->order('pinyin')->select()->toArray();
        $data = ['city_list' => groupByInitials($city_list)];//获取开通城市列表，并排序

        $auth_bool = $this->checkRegionAllAuth();
        $data['hot_city_list'] = [];

        if ($auth_bool) $data['hot_city_list'] = Regions::getHotCityList();//获取热门城市列表

        $result = array('code' => 1, 'msg' => '', 'data' => null);
        $result['data'] = $data;
        $result['msg'] = lang($result['code']);

        return json_encode($result);
    }
}