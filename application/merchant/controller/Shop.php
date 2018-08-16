<?php

namespace app\merchant\controller;

use app\common\model\Shop as ShopM;

class Shop extends Pub
{

    protected $not_login = [''];

    public function index()
    {

    }

    /**
     * 添加店铺
     * @return array
     */
    public function add_shop()
    {
        $shop_name = request()->post('shop_name');
        $wangwang = request()->post('wangwang');
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
}
