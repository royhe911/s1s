<?php

namespace app\admin\controller\auth;

use app\admin\model\AuthGroup;
use app\admin\model\Admin as AdminM;
use app\admin\model\AuthGroupAccess;
use app\common\controller\Backend;
use fast\Random;
use fast\Tree;
use think\Db;
use think\Exception;

/**
 * 管理员管理
 *
 * @icon fa fa-users
 * @remark 一个管理员可以有多个角色组,左侧的菜单根据管理员所拥有的权限进行生成
 */
class Admin extends Backend
{

    protected $model = null;
    protected $childrenGroupIds = [];
    protected $childrenAdminIds = [];
    protected $relationSearch = true;
    protected $layout = 'default2';

    public function initialize()
    {
        parent::initialize();
        $this->model = model('Admin');

        $this->childrenAdminIds = $this->auth->getChildrenAdminIds(true);
        $this->childrenGroupIds = $this->auth->getChildrenGroupIds(true);

        $groupList = AuthGroup::where('id', 'in', $this->childrenGroupIds)->select()->toArray();

        Tree::instance()->init($groupList);
        $this->assignconfig("admin", ['id' => $this->auth->id]);
    }

    /**
     * 查看
     */
    public function index()
    {
        $w[] = ['a.id', 'in', $this->childrenAdminIds];

        $list = (new AdminM)->alias('a')
            ->where($w)
            ->join('auth_group_access g', 'g.uid=a.id')
            ->join('auth_group ag', 'ag.id=g.group_id')
            ->field('a.id, a.username, a.nickname, ag.name, a.email, a.status')
            ->paginate($this->cur_limit, false, ['query' => $this->request->get()]);
        $this->view->assign('list', $list);
        return $this->view->fetch();
    }

    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $group_id = request()->post("group_id");
            if (empty($group_id)) {
                $this->error('角色有误');
            }
            $data['nickname'] = request()->post('nickname');
            $data['realname'] = request()->post('realname');
            $data['mobile'] = request()->post('mobile');
            $data['wx_id'] = request()->post('wx_id');
            $data['qq_id'] = request()->post('qq_id');
            if ($group_id == 13) { // 客服主管
                $data['wx_nickname'] = request()->post('wx_nickname');
                $data['bank_card'] = request()->post('bank_card');
                $data['bank_name'] = request()->post('bank_name');
            }
            if ($group_id == 12) { // 业务员
                $wx_image = request()->file('wx_image');
                $info = $wx_image->move( 'uploads');
            }

            $pass = request()->post('pass');
            $re_pass = request()->post('re_pass');
            if ($pass != $re_pass) {
                $this->error('密码不一致');
            }
            $data['salt'] = Random::alnum();
            $data['password'] = md5(md5($pass) . $data['salt']);
            $data['avatar'] = '/assets/img/avatar.png'; //设置新管理员默认头像。

            $group = [$group_id];
            // 过滤不允许的组别,避免越权
            array_intersect($this->childrenGroupIds, $group);

            Db::startTrans();
            try {
                $id = (new AdminM)->insertGetId($data);
                $data = ['uid' => $id, 'group_id' => $group_id];
                (new AuthGroupAccess())->insert($data);
                Db::commit();
                $this->success('操作成功');
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
        }
        if ($this->auth->isSuperAdmin()) {
            $groupdata = AdminM::getRoleListHtml(Tree::instance()->getTreeArray(0));
        } else {
            $groupdata = '<option  value="14">超级管理组</option>';
        }

        $this->view->assign('groupdata', $groupdata);
        return $this->view->fetch('edit');
    }

    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = $this->model->get(['id' => $ids]);
        if (!$row)
            $this->error(__('No Results were found'));
        if ($this->request->isPost()) {
            $group_id = request()->post("group_id");
            if (empty($group_id)) {
                $this->error('角色有误');
            }
            $data['nickname'] = request()->post('nickname');
            $data['realname'] = request()->post('realname');
            $data['mobile'] = request()->post('mobile');
            $data['wx_id'] = request()->post('wx_id');
            $data['qq_id'] = request()->post('qq_id');
            if ($group_id == 13) { // 客服主管
                $data['wx_nickname'] = request()->post('wx_nickname');
                $data['bank_card'] = request()->post('bank_card');
                $data['bank_name'] = request()->post('bank_name');
            } else {
                $data['wx_nickname'] = $data['bank_card'] = $data['bank_name'] = '';
            }
            if ($group_id == 12) { // 业务员
                $data['wx_image'] = request()->post('wx_image');
            } else {
                $data['wx_image'] = '';
            }
            $pass = request()->post('pass');
            $re_pass = request()->post('re_pass');
            if ($pass != $re_pass) {
                $this->error('密码不一致');
            }
            $data['salt'] = Random::alnum();
            $data['password'] = md5(md5($pass) . $data['salt']);

            $group = [$group_id];
            // 过滤不允许的组别,避免越权
            array_intersect($this->childrenGroupIds, $group);

            $auth_group_access_m = new AuthGroupAccess;
            Db::startTrans();
            try {
                (new AdminM)->where('id', $ids)->update($data);
                $data = ['uid' => $ids, 'group_id' => $group_id];

                $auth_group_access_m->where('uid', $ids)->delete();
                $auth_group_access_m->insert($data);
                Db::commit();
                $this->success('操作成功');
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
        }
        $row['group_id'] = AuthGroupAccess::where('uid', $row['id'])->value('group_id');
        if ($this->auth->isSuperAdmin()) {
            $groupdata = AdminM::getRoleListHtml(Tree::instance()->getTreeArray(0), $row['group_id']);
        } else {
            $groupdata = '<option  value="14">超级管理组</option>';
        }

        $this->view->assign('groupdata', $groupdata);
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids) {
            // 避免越权删除管理员
            $childrenGroupIds = $this->childrenGroupIds;
            $adminList = $this->model->where('id', 'in', $ids)->where('id', 'in', function ($query) use ($childrenGroupIds) {
                $query->name('auth_group_access')->where('group_id', 'in', $childrenGroupIds)->field('uid');
            })->select();
            if ($adminList) {
                $deleteIds = [];
                foreach ($adminList as $k => $v) {
                    $deleteIds[] = $v->id;
                }
                $deleteIds = array_diff($deleteIds, [$this->auth->id]);
                if ($deleteIds) {
                    $this->model->destroy($deleteIds);
                    model('AuthGroupAccess')->where('uid', 'in', $deleteIds)->delete();
                    $this->success();
                }
            }
        }
        $this->error();
    }

    /**
     * 批量更新
     * @internal
     */
    public function multi($ids = "")
    {
        // 管理员禁止批量操作
        $this->error();
    }

    /**
     * 下拉搜索
     */
    protected function selectpage()
    {
        $this->dataLimit = 'auth';
        $this->dataLimitField = 'id';
        return parent::selectpage();
    }

}
