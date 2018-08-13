<?php
namespace app\admin\controller;

use think\Db;
use think\Exception;

use app\admin\model\Admin;
use app\admin\model\AdminPaperwork;
use app\admin\model\AdminRegion;
use app\admin\model\AuthGroupAccess;
use app\common\controller\Backend;
use app\common\model\feigo\Config;
use app\common\model\feigo\Regions;
use app\common\model\feigo\IndexGoods;  

class Merchant extends Backend
{
    protected $relationSearch = true;
    protected $layout = 'default2';

    /*
     * 商户列表
     */
    public function index()
    {
        $list_rows = $this->cur_limit;
        $keyword = request()->get('keyword');
        $status = request()->get('status', -1);
        $from_time = request()->get('from_time');
        $w = [];

        $w[] = ['g.group_id', '=', 2];
        if ($keyword != null && !empty($keyword)) $w[] = ['p.name|p.company_name', 'like', '%' . $keyword . '%'];
        if ($status >= 0) $w[] = ['a.status', '=', $status];
        $str = betweenTime($from_time, 'a.createtime');
        if (!empty($str)) $w[] = $str;

        $list = (new Admin())->alias('a')
            ->with('adminCity')
            ->join('admin_paperwork p', 'p.admin_id=a.id')
            ->join('auth_group_access g', 'g.uid=a.id')
            ->where($w)->group('a.id')
            ->field('a.*, p.company_name, p.license_number, p.name, p.person_id_number, p.company_address')
            ->order('a.id', 'desc')
            ->paginate($list_rows, false, ['query' => $this->request->get()]);

        $support_city_ids = Config::getValue('support_city');
        $city_list = Regions::where('id', 'in', $support_city_ids)->column('name', 'id');

        $this->view->assign('list', $list);
        $this->view->assign('status', $status);
        $this->view->assign('city_list', $city_list);
        return $this->view->fetch();
    }

    /**
     * 新增
     * @return string
     */
    public function add()
    {
        if (request()->isAjax()) {
            $company_name = request()->post('company_name');
            $city_id = request()->post('city_id');
            $license_img = request()->post('license_img');
            $license_number = request()->post('license_number');
            $name = request()->post('name');
            $banksite = request()->post('banksite');
            $bankcard = request()->post('bankcard');
            $person_img_p = request()->post('person_img_p');
            $person_img_n = request()->post('person_img_n');
            $person_id_number = request()->post('person_id_number');
            $contacts = request()->post('contacts');
            $mobile = request()->post('mobile');
            $email = request()->post('email');
            $company_address = request()->post('company_address');
            $username = request()->post('username');

            if ($company_name == null || $city_id == null || $license_img == null || $person_img_p == null || $person_img_n == null || $person_id_number == null || $contacts == null || $mobile == null || $email == null || $company_address == null || $username == null) {
                $this->error(__('请完善城市合伙人信息！'));
            }
            if (Admin::where('mobile', $mobile)->count() > 0) {
                $this->error(__('该手机号已被注册！'));
            }
            if (Admin::where('username', $username)->count() > 0) {
                $this->error(__('该管理员账号已被注册！'));
            }
            if (AdminRegion::where('region_id', 'in', $city_id)->find()) {
                $this->error(__('所选城市已关联合伙人！'));
            }
            if (Regions::where('id', 'in', $city_id)->group('parent_id')->count() > 1) {
                $this->error(__('不可选择跨省的城市！'));
            }

            $city_arr = explode(',', trim($city_id, ','));
            if (empty($city_arr) || !is_array($city_arr)) {
                $this->error(__('请选择管理的城市！'));
            } else {
                $parent_id_arr = [];

                foreach ($city_arr as $value) {
                    $sup_data = Regions::getSup($value);
                    if ($sup_data) {
                        $parent_id_arr[] = $sup_data['id'];
                    }
                }
                if (count(array_unique($parent_id_arr)) != 1) $this->error(__('不可选择跨省的城市！'));
            }

            $time = date('Y-m-d H:i:s', time());
            $data = [
                'username' => $username,
                'avatar' => '/assets/img/avatar.png',
                'email' => $email,
                'mobile' => $mobile,
                'contacts' => $contacts,
                'city_id' => $city_id,
                'createtime' => $time,
                'updatetime' => $time,
                'status' => 2
            ];

            Db::startTrans();
            try {
                $admin_id = (new Admin())->insertGetId($data);

                // 证件信息
                $data = [
                    'name' => $name,
                    'license_number' => $license_number,
                    'license_img' => $license_img,
                    'person_img_p' => $person_img_p,
                    'person_img_n' => $person_img_n,
                    'person_id_number' => $person_id_number,
                    'bankcard' => $bankcard,
                    'banksite' => $banksite,
                    'company_name' => $company_name,
                    'company_address' => $company_address,
                    'admin_id' => $admin_id,
                ];
                (new AdminPaperwork())->insert($data);

                // 加入城市
                $data = [];
                foreach ($city_arr as $v) $data[] = ['region_id' => $v, 'admin_id' => $admin_id];

                (new AdminRegion())->insertAll($data);

                $group = [2];
                // 过滤不允许的组别,避免越权
                $group = array_intersect($this->auth->getChildrenGroupIds(true), $group);
                $dataset = [];
                foreach ($group as $value) $dataset[] = ['uid' => $admin_id, 'group_id' => $value];

                (new AuthGroupAccess())->saveAll($dataset);

                (new IndexGoods())->whereIn('region_id_2', trim($city_id, ','))->delete();//删除默认的推荐商品数据

                Db::commit();
            } catch (Exception $e) {
                Db::rollback();
                $this->error(__('Operation failed'));
            }

            if (isset($admin_id)) {
                // 发送邮件
                $sign = authcode($username . '|' . $admin_id, 'ENCODE', '', 3600*48);
                //$url = config('url_domain_root') . '/index/activation_merchant?sign=' . urlencode($sign);
                $url = 'https://' . config('url_prefix_www') . '.' . config('url_domain_root') . '/index/activation_merchant_view?sign=' . urlencode($sign);
                $mes = '尊敬的千寻狗城市合伙人: 请在收到激活邮件后的48小时内，通过点击下面的链接进行操作，并且设置新的登录密码进行激活
                <a target=\'_blank\' href="' . $url . '">' . $url . '</a>（如果上面不是链接形式，请将该地址手动复制粘贴到浏览器地址栏再访问）在上面的链接所打开的页面中输入新的密码后提交，您即可使用新密码登录网站了，网站地址为：http://' . config('url_prefix_admin') . '.' . config('url_domain_root');
                \Mail::sendEmail($email, '千寻狗后台激活通知', $mes);
                // 发送短信
                sendSms(3, $mobile);
                $this->success(__('Operation completed'));
            }
            $this->error(__('Operation failed'));
        }
        return $this->view->fetch('edit');
    }

    /**
     * 编辑
     * @param null $ids
     * @return string
     */
    public function edit($ids = null)
    {
        $row = (new Admin())->alias('a')
            ->join('admin_paperwork p', 'p.admin_id=a.id')
            ->where('a.id', $ids)
            ->where('a.status', 'in', [0, 2])
            ->field('a.*, p.company_name, p.license_number, p.license_img, p.person_img_p, p.person_img_n, p.bankcard, p.banksite, p.name, p.person_id_number, p.company_address')
            ->find();
        if (empty($row)) $this->error('没有相关信息或状态是启用的');
        if (request()->isAjax()) {
            $company_name = request()->post('company_name');
            $city_id = request()->post('city_id');
            $license_img = request()->post('license_img');
            $license_number = request()->post('license_number');
            $name = request()->post('name');
            $banksite = request()->post('banksite');
            $bankcard = request()->post('bankcard');
            $person_img_p = request()->post('person_img_p');
            $person_img_n = request()->post('person_img_n');
            $person_id_number = request()->post('person_id_number');
            $contacts = request()->post('contacts');
            $mobile = request()->post('mobile');
            $email = request()->post('email');
            $company_address = request()->post('company_address');

            if ($company_name == null || $city_id == null || $license_img == null || $person_img_p == null || $person_img_n == null || $person_id_number == null || $contacts == null || $mobile == null || $email == null || $company_address == null) {
                $this->error(__('请完善城市合伙人信息！'));
            }
            if (Admin::where('mobile', $mobile)->where('id', '<>', $ids)->count() > 0) {
                $this->error(__('该手机号已被注册！'));
            }
            if (AdminRegion::where('region_id', 'in', $city_id)->where('admin_id', '<>', $ids)->find()) {
                $this->error(__('所选城市已关联合伙人！'));
            }
            if (Regions::where('id', 'in', $city_id)->group('parent_id')->count() > 1) {
                $this->error(__('不可选择跨省的城市！'));
            }

            $city_arr = explode(',', trim($city_id, ','));
            if (empty($city_arr) || !is_array($city_arr)) {
                $this->error(__('请选择管理的城市！'));
            }

            $time = date('Y-m-d H:i:s', time());
            $data = [
                'email' => $email,
                'mobile' => $mobile,
                'contacts' => $contacts,
                'city_id' => $city_id,
                'updatetime' => $time,
            ];

            Db::startTrans();
            try {
                (new Admin())->where('id', $ids)->update($data);

                // 证件信息
                $data = [
                    'name' => $name,
                    'license_number' => $license_number,
                    'license_img' => $license_img,
                    'person_img_p' => $person_img_p,
                    'person_img_n' => $person_img_n,
                    'person_id_number' => $person_id_number,
                    'bankcard' => $bankcard,
                    'banksite' => $banksite,
                    'company_name' => $company_name,
                    'company_address' => $company_address,
                ];
                (new AdminPaperwork())->where('admin_id', $ids)->update($data);

                // 加入城市
                (new AdminRegion)->where('admin_id', $ids)->delete();
                $data = [];
                foreach ($city_arr as $v) {
                    $data[] = ['region_id' => $v, 'admin_id' => $ids];
                }
                (new AdminRegion())->insertAll($data);

                Db::commit();
                $this->success(__('Operation completed'));
            } catch (Exception $e) {
                Db::rollback();
                $this->error(__('Operation failed'));
            }
        }

        $region_ids = AdminRegion::where('admin_id', $ids)->column('region_id');
        $region_data = [];
        if (!empty($region_ids)){
            $region_data = Regions::whereIn('id', $region_ids)->field('id, parent_id, name')->select()->toArray();
        }

        $this->view->assign('regions_data', $region_data);
        $this->view->assign('row', $row);
        return $this->view->fetch('edit');
    }

    /**
     * 编辑状态
     */
    public function edit_status()
    {
        $ids = request()->post('ids');
        $status = request()->post('status');

        if (empty($ids) || $status == null)
            $this->error(__('Parameter exception'));

        $row = Admin::get($ids);
        if (!$row)
            $this->error(__('No Results were found'));

        if (Admin::where('id', 'in', $ids)->where('status', $status)->find()) {
            $this->error(__('有' . ($status == 1 ? '启用' : '禁用') . '的城市合伙人，请重新选择！'));
        }
        if (Admin::where('id', 'in', $ids)->where('status', 2)->find()) {
            $this->error(__('有激活中的城市合伙人，请重新选择！'));
        }

        if (Admin::where('id', 'in', $ids)->setField('status', $status)) {
            $this->success(__('Operation completed'));
        }
        $this->error(__('Operation failed'));
    }

    /**
     * 删除城市合伙人
     * @throws \think\exception\DbException
     */
    public function delete()
    {
        $ids = request()->post('ids');
        if (empty($ids))
            $this->error(__('Parameter exception'));

        $row = Admin::get($ids);
        if (!$row)
            $this->error(__('No Results were found'));

        if ($row['status'] == 1) {
            $this->error(__('有启用的城市合伙人，请重新选择！'));
        }
        Db::startTrans();

        try {
            // 删除城市合伙人
            Admin::where(['id' => $ids])->delete();
            // 删除权限
            AuthGroupAccess::where(['uid' => $ids])->delete();
            // 删除信息
            AdminPaperwork::where(['admin_id' => $ids])->delete();
            // 删除绑定的城市
            AdminRegion::where(['admin_id' => $ids])->delete();

            Db::commit();
            $this->success(__('Operation completed'));
        } catch (Exception $e) {
            $this->error(__('Operation failed'));
        }
    }

    /**
     * 地区列表
     * @return string
     */
    public function region_list()
    {
        $list_rows = $this->default_limit;
        $admin_id = request()->get('admin_id');//合伙人id
        $region_id_1 = request()->get('region_id_1');
        $name = request()->get('name');

        $where = [];
        if (!empty($admin_id)) $where[] = ['admin_id', '<>', $admin_id];
        $used_city_id = AdminRegion::where($where)->column('region_id');
        $city_id = Config::getValue('support_city');

        if (is_array($used_city_id) && !empty($used_city_id)){
            $city_id = array_diff($city_id, $used_city_id);
        }

        if ($region_id_1 != null) {
            $city_id = Regions::where('parent_id', $region_id_1)->where('id', 'in', $city_id)->column('id');
        }

        $where = [];
        $where[] = ['r.level_type', '=', 2];
        if (!empty($name)) $w[] = ['r.name', 'like', '%' . $name . '%'];
        $w[] = ['r.id', 'in', $city_id];

        $city_list = (new Regions)
            ->alias('r')
            ->join('regions re', 're.id=r.parent_id')
            ->field('r.*, re.name as region_name')
            ->where($w)
            ->paginate($list_rows, false, ['query' => $this->request->get()]);

        $region_list_1 = Regions::where('level_type', 1)->column('name', 'id');
        $this->view->assign('city_list', $city_list);
        $this->view->assign('region_list_1', $region_list_1);
        return $this->view->fetch();
    }

    /**
     * 获取下级列表
     */
    public function get_city()
    {
        $pid = request()->param('pid');

        $city_list = Regions::where('parent_id', $pid)
            ->where('level_type', 2)
            ->where('id', 'in', Config::getValue('support_city'))
            ->field('id, name')->select()->toArray();

        $this->success(__('Operation completed'), '', $city_list);
    }
}
