<?php

namespace app\admin\controller;

use app\common\model\Merchant as MerchantM;
use app\common\controller\Backend;

/**
 * 商户管理
 * @internal
 */
class Merchant extends Backend
{
    protected $relationSearch = true;
    protected $layout = 'default2';

    /**
     * 商户列表
     */
    public function index()
    {
        $w = [];
        $keyword = request()->get('keyword');
        if ($keyword != null) $w[] = ['m.username|m.mobile|m.wx_id', 'like', '%' . $keyword . '%'];
        if (in_array(12, $this->auth->getGroupIds())) { // 业务员
            $w[] = ['a.id', '=', session('admin')['id']];
        }
        $list = (new MerchantM)
            ->alias('m')
            ->where($w)
            ->field('m.*, a.nickname')
            ->join('admin a', 'a.id=m.s_id', 'left')
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
            MerchantM::where('id', $id)->setField('status', $status);
        } else {
            MerchantM::where('id', $id)->setField('status', $status);
            // TODO 代写冻结账号的相关操作。
        }
        return $this->result($this->result, 0, '', 'json');
    }
}