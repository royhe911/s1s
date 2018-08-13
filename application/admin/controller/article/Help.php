<?php

namespace app\admin\controller\article;

use app\common\controller\Backend;
use app\common\model\feigo\HelpType;
use app\common\model\feigo\Help as HelpM;
use app\common\model\feigo\HelpTypeHelp;

class Help extends Backend
{
    protected $relationSearch = true;
    protected $layout = 'default2';

    private $position = [1 => '用户端web', 2 => '用户端app', 3 => '商家端web', 4 => '商家端app'];

    /**
     * 列表
     * @return string|\think\response\Json
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $list_rows = $this->cur_limit;
        $type_id = request()->get('type_id');
        $from_time = request()->get('from_time');

        $w = [];

        if ($type_id != null) $w[] = ['h.type_id', '=', $type_id];
        $str = betweenTime($from_time, 'h.create_time');
        if (!empty($str)) $w[] = $str;

        $list = (new HelpM())->alias('h')
            ->join('help_type t', 'h.type_id=t.id')
            ->field('h.*, t.name')
            ->where($w)
            ->order('h.create_time', 'desc')
            ->paginate($list_rows, false, ['query' => $this->request->get()]);

        $type_list = HelpType::getAllType();
        $this->view->assign('list', $list);
        $this->view->assign('type_list', $type_list);
        return $this->view->fetch();
    }

    /**
     * 新增
     * @return string
     */
    public function add()
    {
        if (request()->isAjax()) {
            $title = request()->post('title');
            $type_id = request()->post('type_id');
            $is_show = request()->post('is_show', 1);
            $content = request()->post('content', '', 'htmlspecialchars');
            $position = request()->post('position');
            if (strstr($title, '%') !== false) $this->error(__('名称中有非法字符！'));

            $is_empty = (empty($title) || empty($type_id) || empty($content));
            $is_empty && $this->error(__('请完善帮助信息！'));

            $data = [
                'title' => $title,
                'type_id' => $type_id,
                'is_show' => $is_show,
                'content' => $content,
                'position' => $position,
                'create_time' => date('Y-m-d H:i:s', time())
            ];

            $tag = ['help_center_app_b'];
            $id = (new HelpM())->insertGetId($data);
            if ($id) {
                // 极光推送
                jPush('《' . $title . '》点击查看....', [], $tag, [
                    'sound' => 'sound',
                    'badge' => '+1',
                ], [], '', [
                    'title' => '《' . $title . '》点击查看....',
                    'extras' => ['category' => '2', 'sign_id' => '3', 'id' => "{$id}"]
                ]);
                $this->success(__('Operation completed'));
            }
            $this->error(__('Operation failed'));
        }

        $type_list = HelpType::getAllType();
        $this->view->assign('type_list', $type_list);
        return $this->view->fetch('edit');
    }

    /**
     * 编辑
     * @param null $ids
     * @return string
     */
    public function edit($ids = null)
    {
        $row = HelpM::get($ids);
        if (!$row) $this->error(__('No Results were found'));
        if ($row['is_show']) $this->error(__('启用状态的数据不可编辑！'));

        if (request()->isAjax()) {
            $title = request()->post('title');
            $type_id = request()->post('type_id');
            $is_show = request()->post('is_show');
            $content = request()->post('content', '', 'htmlspecialchars');
            $position = request()->post('position');
            if (strstr($title, '%') !== false) $this->error(__('名称中有非法字符！'));

            $is_empty = (empty($title) || empty($type_id) || empty($content));
            $is_empty && $this->error(__('请完善帮助信息！'));

            $data = [
                'title' => $title,
                'type_id' => $type_id,
                'is_show' => $is_show,
                'content' => $content,
                'position' => $position,
                'update_time' => date('Y-m-d H:i:s', time())
            ];

            if (HelpM::where('id', $ids)->update($data)) $this->success(__('Operation completed'));
            $this->error(__('Operation failed'));
        }

        $row['content'] = $row['content'] ? htmlspecialchars_decode($row['content']) : '';
        $type_list = HelpType::getAllType();

        $this->view->assign('type_list', $type_list);
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

        $row = HelpM::get($ids);
        if (!$row)
            $this->error(__('No Results were found'));

        if (HelpM::where('id', 'in', $ids)->where('is_show', $status)->find()) {
            $this->error(__('有' . ($status == 1 ? '显示' : '隐藏') . '的帮助文档，请重新选择！'));
        }

        if (HelpM::where('id', 'in', $ids)->setField('is_show', $status)) {
            $this->success(__('Operation completed'));
        }
        $this->error(__('Operation failed'));
    }

    /**
     * 删除
     */
    public function delete()
    {
        $ids = request()->post('ids');
        if (empty($ids))
            $this->error(__('Parameter exception'));

        $row = HelpM::get($ids);
        if (!$row)
            $this->error(__('No Results were found'));

        if (HelpM::where('id', 'in', $ids)->where('is_show', 1)->find()) {
            $this->error(__('有帮助文档显示状态，不能删除'));
        }

        if (HelpM::where('id', 'in', $ids)->delete()) {
            $this->success(__('Operation completed'));
        }
        $this->error(__('Operation failed'));
    }
}
