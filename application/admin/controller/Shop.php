<?php

namespace app\admin\controller;

use app\common\model\Shop as ShopM;
use app\common\controller\Backend;

/**
 * 店铺管理
 * @internal
 */
class Shop extends Backend
{
    protected $relationSearch = true;
    protected $layout = 'default2';

    /**
     * 店铺列表
     */
    public function index()
    {
        $w = [];
        $keyword = request()->get('keyword');
        if ($keyword != null) $w[] = ['m.username|m.mobile|m.wx_id', 'like', '%' . $keyword . '%'];
        if (in_array(12, $this->auth->getGroupIds())) { // 业务员
            $w[] = ['m.s_id', '=', session('admin')['id']];
        }
        $list = (new ShopM())
            ->alias('s')
            ->where($w)
            ->field('s.*, m.nickname')
            ->join('merchant m', 'm.id=s.m_id')
            ->paginate($this->cur_limit, false, ['query' => $this->request->get()]);
        $this->view->assign('list', $list);
        return $this->view->fetch();
    }

    /**
     * 商户账号状态
     */
    public function edit_status()
    {
        $id = request()->post('id');
        $status = request()->post('status');

        if ($id == null || $status == null) {
            $this->result['code'] = '10';
            $this->result['msg'] = lang($this->result['code']);
            return $this->result($this->result, 0, '', 'json');
        }

        if ($status == 1) {
            ShopM::where('id', $id)->setField('status', $status);
        } else {
            ShopM::where('id', $id)->setField('status', $status);
            // TODO 代写冻结账号的相关操作。
        }
        return $this->result($this->result, 0, '', 'json');
    }
}