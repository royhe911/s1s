<?php

namespace app\merchant\controller;

use app\common\model\Shop as ShopM;
use think\Config;

class Product extends Pub
{

    protected $not_login = [''];

    public function index()
    {

    }

    public function add_product()
    {
        if (request()->isPost()) {
            $shop_id = request()->post('shop_id');
            $good_type_id = request()->post('good_type_id');
            $sign_keyword = request()->post('sign_keyword');
            $good_title = request()->post('good_title');
            $good_url = request()->post('good_url');
            $good_image = request()->post('good_image');

            $url = request()->post('url');
            if (empty($shop_name) || empty($wangwang) || empty($url)) {
                $this->result['code'] = '10';
                $this->result['msg'] = lang($this->result['code']);
                return $this->result;
            }

            $data = [
                'm_id' => $this->merchant_info['id'],
                'shop_name' => $shop_name,
                'url' => $url,
                'wangwang' => $wangwang,
                'status' => 3,
                'addtime' => time(),
            ];
            if (ShopM::create($data)) {
                return $this->result;
            }
            $this->result['code'] = '10';
            $this->result['msg'] = lang($this->result['code']);
            return $this->result;
        }
        // 返回相关信息
        $shop_list = ShopM::where('m_id', $this->merchant_info['id'])->column('shop_name', 'id');
        $good_type = config('good_type');
        $ancillary_service = config('ancillary_service');
        $this->result['data']['shop_list'] = $shop_list;
        $this->result['data']['good_type'] = $good_type;
        $this->result['data']['ancillary_service'] = $ancillary_service;
        return $this->result;
    }
}
