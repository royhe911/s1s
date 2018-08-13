<?php
namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\model\feigo\Config;
use app\common\model\feigo\MemberProcessor;
use app\common\model\feigo\Members;
use app\common\model\feigo\MemberShopHistory;
use app\common\model\feigo\Regions;
use app\common\model\feigo\ShopComment;
use think\Db;
use think\Exception;

class Member extends Backend
{
    protected $relationSearch = true;
    protected $layout = 'default2';

    /**
     * 用户列表
     * @return string|\think\response\Json
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $list_rows = $this->cur_limit;
        $keyword = request()->get('keyword');
        $status = request()->get('status', -1);
        $type = request()->get('type');
        $time = request()->param('from_time');

        $w = [];
        if ($keyword != null && !empty($keyword)) $w[] = ['m.nickname|m.mobile', 'like', '%' . $keyword . '%'];
        if ($status >= 0) $w[] = ['m.status', '=', $status];
        if ($type == 1) $w[] = ['p.id', '>', 0];
        $str = betweenTime($time, 'm.create_time');
        if (!empty($str)) $w[] = $str;

        $list = (new Members)->alias('m')
            ->join('member_processor p', 'm.id=p.member_id', 'left')
            ->join('regions r', 'r.id=p.region_id', 'left')
            ->where($w)->order('m.create_time desc')
            ->field('m.*, p.id as processor_id, r.name as region_name')
            ->paginate($list_rows, false, ['query' => $this->request->get()]);

        $this->view->assign('list', $list);
        $this->view->assign('status', $status);
        $this->view->assign('is_super', $this->auth->check('dashboard/has_look_fields'));

        return $this->view->fetch();
    }

    /**
     * 用户信息
     * @param null $ids
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function info($ids = null)
    {
        $row = (new Members)->alias('m')
            ->join('member_processor p', 'm.id=p.member_id', 'left')
            ->join('regions r', 'r.id=p.region_id', 'left')
            ->where('m.id', $ids)
            ->field('m.*, p.id as processor_id, r.name as region_name')
            ->find();

        $this->view->assign('member_info', $row);
        $this->view->assign('is_super', $this->auth->check('dashboard/has_look_fields'));
        return $this->view->fetch();
    }

    /**
     * 评论记录
     * @return string
     */
    public function comment($ids = null)
    {
        $list_rows = $this->default_limit;

        $list = (new ShopComment())->alias('c')
            ->join('shops s', 's.id=c.shop_id')
            ->where('c.member_id', $ids)
            ->field('c.id, s.name, c.score, c.content, c.create_time')
            ->order('c.create_time desc')
            ->paginate($list_rows, false, ['query' => $this->request->get()]);
        $this->view->assign('list', $list);
        return $this->view->fetch('member/info/comment');
    }

    /**
     * 导航记录
     */
    public function navigation($ids = null)
    {
        $list_rows = $this->default_limit;

        $list = (new MemberShopHistory())->alias('m')
            ->join('shops s', 's.id=m.shop_id')
            ->where('m.member_id', $ids)
            ->where('m.type', 2)
            ->field('m.id, m.create_time, s.name, s.address')
            ->order('m.create_time desc')
            ->paginate($list_rows, false, ['query' => $this->request->get()]);
        $this->view->assign('list', $list);
        return $this->view->fetch('member/info/navigation');
    }

    /**
     * 联系记录
     * @return string
     */
    public function contact($ids = null)
    {
        $list_rows = $this->default_limit;

        $list = (new MemberShopHistory())->alias('m')
            ->join('shops s', 's.id=m.shop_id')
            ->where('m.member_id', $ids)
            ->where('m.type', 1)
            ->field('m.id, m.create_time, s.name, s.address')
            ->order('m.create_time desc')
            ->paginate($list_rows, false, ['query' => $this->request->get()]);
        $this->view->assign('list', $list);
        return $this->view->fetch('member/info/contact');
    }

    /**
     * 编辑
     * @param null $ids
     * @return string
     */
    public function edit($ids = null)
    {
        if (request()->isAjax()) {
            $avatar = request()->post('avatar');
            $nickname = request()->post('nickname');
            $sex = request()->post('sex');
            $birthday = request()->post('birthday');
            $mobile = request()->post('mobile');
            if ($nickname == null || $sex == null || $mobile == null) {
                $this->error(__('请完善用户信息！'));
            }

            if (Members::where('id', '<>', $ids)->where('mobile', $mobile)->find()) {
                $this->error(__('该手机号已被注册！'));
            }

            $time = date('Y-m-d H:i:s', time());
            $data = [
                'avatar' => $avatar,
                'nickname' => $nickname,
                'sex' => $sex,
                'birthday' => $birthday,
                'mobile' => $mobile,
                'update_time' => $time
            ];

            if (Members::where('id', $ids)->update($data)) {
                $this->success(__('Operation completed'));
            }

            $this->error(__('Operation failed'));
        }

        $row = Members::where('id', $ids)->find();
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

        if (Members::where('id', 'in', $ids)->where('status', $status)->find()) {
            $this->error(__('有' . ($status == 1 ? '启用' : '禁用') . '的用户，请重新选择！'));
        }

        if (Members::where('id', 'in', $ids)->setField('status', $status)) {
            $this->success(__('Operation completed'));
        }
        $this->error(__('Operation failed'));
    }

    /**
     * @param null $ids
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add_operations($ids = null)
    {
        $member_processor = MemberProcessor::where('member_id', $ids)->find();
        $support_city = Config::getValue('support_city');
        if (request()->isPost()) {
            $region_id_2 = request()->post('region_id_2');
            $nickname = request()->post('nickname');
            if (empty($region_id_2) || empty($nickname)) {
               $this->error(__('Parameter exception'));
            }
            if (!in_array($region_id_2, $support_city)) {
                $this->error('没有这个城市或城市没有开通');
            }
            $data = [
                'member_id' => $ids,
                'region_id' => $region_id_2,
                'nickname' => $nickname,
            ];
            Db::startTrans();
            try {
                if (empty($member_processor)) {
                    $data['create_time'] = date('Y-m-d H:i:s', time());
                    MemberProcessor::create($data);
                } else {
                    MemberProcessor::where('id', $member_processor['id'])->update($data);
                }

                Members::where('id', $ids)->setField('is_processor', 1);
                Db::commit();
                $this->success(__('Operation completed'));
            } catch (Exception $e) {
                Db::rollback();
                $this->error(__('Operation failed'));
            }
        }
        $region_id_1 = $region_id_2 = '';
        if (!empty($member_processor)) {
            $region_id_2 = $member_processor['region_id'];
            $region_id_1 = Regions::getPreId($region_id_2);
        }
        $province_list = Regions::where('level_type', 1)
            ->where('id', 'in', function ($query) use ($support_city) {
                $query->name('regions')->where('id', 'in', $support_city)->field('parent_id')->select();
            })
            ->field('id as region_id, name, parent_id, short_name, level_type')
            ->order("pinyin")->select()->toArray();

        $this->view->assign('region_id_1', $region_id_1);
        $this->view->assign('region_id_2', $region_id_2);
        $this->view->assign('province_list', $province_list);
        $this->view->assign('member_processor', $member_processor);
        return $this->view->fetch('add_operations');
    }

    /**
     * 获取下级列表
     */
    public function get_city()
    {
        $pid = request()->param('pid');
        $support_city = Config::getValue('support_city');

        $city_list = Regions::where('parent_id', $pid)->where('id', 'in', $support_city)->field('id, name')->select()->toArray();

        $this->success(__('Operation completed'), '', $city_list);
    }
}
