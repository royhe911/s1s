<?php
namespace app\admin\controller\shopowner;

use app\common\controller\Backend;
use app\common\model\feigo\BusinessMember;
use app\common\model\feigo\BusinessShop;
use app\common\model\feigo\Regions;
use app\common\model\feigo\Shops;
use app\common\model\feigo\ShopTypes;
use fast\Random;

class Owner extends Backend
{

    protected $relationSearch = true;
    protected $layout = 'default2';

    protected $device = [
        1 => 'Android',  2 => 'IOS', 3 => '小程序', 4 => 'PC'
    ];

    protected $business_type = [
        1 => '自营', 2 => '代理', 3 => '直营', 4 => '加盟'
    ];

    /**
     * 商户列表
     * @return string|\think\response\Json
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $list_rows = $this->cur_limit;

        $keyword = request()->get('keyword');
        $status = request()->get('status', -1);
        $from_time = request()->get('from_time');

        $w = [];
        if ($keyword != null && !empty($keyword)) $w[] = ['b.nickname|b.mobile', 'like', '%' . $keyword . '%'];
        if ($status >= 0) $w[] = ['b.status', '=', $status];
        $str = betweenTime($from_time, 'b.create_time');
        if (!empty($str)) $w[] = $str;

        $list = (new BusinessMember())->alias('b')
            ->join('business_shop bs', 'bs.business_id=b.id', 'left')
            ->field('b.id, b.nickname, b.mobile, b.create_time, b.device, b.status, bs.status as is_status')
            ->order('b.create_time desc')
            ->where($w)->group('b.id')
            ->paginate($list_rows, false, ['query' => $this->request->get()]);

        $this->view->assign('list', $list);
        $this->view->assign('status', $status);
        $this->view->assign('device', $this->device);
        $this->view->assign('is_super', $this->auth->check('dashboard/has_look_fields'));
        return $this->view->fetch();
    }

    /**
     * 重置密码
     * @return string
     * @throws \think\exception\DbException
     */
    public function re_pass()
    {
        $business_id = request()->param('business_id');
        $business = BusinessMember::get($business_id);
        if (empty($business)) $this->error('没有找到该商户');

        if (request()->isAjax()) {
            $password = request()->post('pass');
            if (empty($password)) $this->error(__('缺少参数'));

            $salt = Random::alnum();
            $pass = BusinessMember::encryptPassword($password, $salt);
            $data = ['salt' => $salt, 'password' => $pass];
            if (BusinessMember::where('id', $business_id)->update($data)) {
                // 发送短信
                sendSms(7, $business['mobile']);
                $this->success(__('Operation completed'));
            }
            $this->error(__('Operation failed'));
        }
        $this->view->assign('business_id', $business_id);
        return $this->view->fetch();
    }

    /**
     * 商户店铺详情
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function look_shop()
    {
        $business_id = request()->get('business_id');
        if (empty($business_id)) {
            $this->error(__('Parameter exception'));
        }
        $id = BusinessShop::where('business_id', $business_id)->where('status', 1)->value('shop_id');
        if (empty($id)) {
            $this->error('没有认领的门店');
        }
        $row = (new Shops())->alias('a')
            ->with('shopTypeShop')
            ->with('region_1')
            ->with('region_2')
            ->with('region_3')
            ->with('region_4')
            ->where('a.id', $id)
            ->leftJoin('shop_paperwork c', 'c.shop_id=a.id')
            ->field("a.*, c.id as paperwork_id, c.name as truename, c.license_number, c.license_img, c.person_img_p, c.person_img_n, c.person_img_s, c.person_id_number, c.business_type, c.banksite, c.bankcard, c.license_type, c.license_name")
            ->find();
        $business_hours_new = $row['business_hours_new'];
        if (!empty($business_hours_new)) {
            $business_hours_new = handleBusinessHours($business_hours_new);
            $row['business_hours_new'] = $business_hours_new;
        }

        //附加数据
        $shop_type = (new ShopTypes())->where('is_show', 1)->column('name', 'id');//门店类型列表
        $region = Regions::column('name', 'id');

        $this->assign('city_id', $this->city_id);
        $this->assign('business_type', $this->business_type);
        $this->assign('has_auth', $this->auth->check('dashboard/certified'));
        $this->assign('shop_type', $shop_type);
        $this->assign('region', $region);
        $this->assign('row', $row);
        return $this->view->fetch();
    }

    /**
     * 启用禁用商户
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit_status()
    {
        $ids = request()->post('ids');
        $status = request()->post('status');
        if (empty($ids) || $status == null)
            $this->error(__('Parameter exception'));

        if (BusinessMember::where('id', 'in', $ids)->where('status', $status)->find()) {
            $this->error(__('有' . ($status == 1 ? '启用' : '禁用') . '的商户，请重新选择！'));
        }

        if (BusinessMember::where('id', 'in', $ids)->setField('status', $status)) {
            $this->success(__('Operation completed'));
        }
        $this->error(__('Operation failed'));
    }
}
