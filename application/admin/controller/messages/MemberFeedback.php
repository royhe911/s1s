<?php
namespace app\admin\controller\messages;

use app\common\controller\Backend;
use app\common\model\feigo\Feedback;

class MemberFeedback extends Backend
{

    protected $relationSearch = true;
    protected $layout = 'default2';

    protected $device = [
        1 => 'Android',  2 => 'IOS', 3 => '小程序', 4 => 'PC'
    ];

    /**
     * 反馈列表
     * @return string|\think\response\Json
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $list_rows = $this->cur_limit;

        $keyword = request()->get('keyword');
        $device = request()->get('device');
        $from_time = request()->get('from_time');

        $w[] = ['position', '=', 0];
        if ($keyword != null && !empty($keyword)) $w[] = ['f.content', 'like', '%' . $keyword . '%'];
        if ($device != null && !empty($device)) $w[] = ['f.device', '=', $device];
        $str = betweenTime($from_time, 'f.create_time');
        if (!empty($str)) $w[] = $str;

        $list = (new Feedback())->alias('f')
            ->join('members m', 'm.id=f.member_id', 'left')
            ->field('f.id, f.create_time, f.device, m.nickname, m.mobile')
            ->order('f.create_time desc')
            ->where($w)->group('f.id')
            ->paginate($list_rows, false, ['query' => $this->request->get()]);

        $this->view->assign('list', $list);
        $this->view->assign('device', $this->device);
        $this->view->assign('is_super', $this->auth->check('dashboard/has_look_fields'));
        return $this->view->fetch();
    }

    /**
     * 反馈详情
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function feedback_content()
    {
        $ids = request()->get('ids');

        if (empty($ids))
            $this->error(__('Parameter exception'));

        $feedback = (new Feedback())->alias('f')
            ->join('members m', 'm.id=f.member_id', 'left')
            ->field('f.id, f.create_time, f.content, m.nickname, m.mobile')
            ->where('f.id', $ids)->group('f.id')
            ->find();

        if (empty($feedback)) $this->error('没有找到该反馈信息');

        $this->view->assign('is_super', $this->auth->check('dashboard/has_look_fields'));
        $this->view->assign('feedback', $feedback);
        return $this->view->fetch();
    }

    /**
     * 删除反馈
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delete()
    {
        $ids = request()->post('ids');
        if (empty($ids))
            $this->error(__('Parameter exception'));

        if (Feedback::where('id', 'in', $ids)->delete()) {
            $this->success(__('Operation completed'));
        }
        $this->error(__('Operation failed'));
    }
}
