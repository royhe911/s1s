<?php
namespace app\admin\controller\sysconf;

use app\common\controller\Backend;
use app\common\model\feigo\HotKeyword as HotKeywordM;
use think\facade\Cache;

class HotKeyword extends Backend
{
    protected $relationSearch = true;
    protected $layout = 'default2';

    public function index()
    {
        $list = HotKeywordM::getList();

        $this->view->assign('list', $list);
        return $this->view->fetch();
    }

    /**
     * 新增
     * @return string
     */
    public function add()
    {
        if (request()->isAjax()) {
            $name = request()->post('name');
            $sort = request()->post('sort', 0);

            if ($name == null) $this->error(__('请完善热搜关键词信息！'));
            if (!is_numeric($sort)) $this->error(__('非法请求！'));

            $data = ['name' => $name, 'sort' => $sort];
            if (HotKeywordM::create($data)) {
                Cache::rm('hot_keyword_all');
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
        $row = HotKeywordM::get($ids);
        if (!$row) $this->error(__('No Results were found'));

        if (request()->isAjax()) {
            $name = request()->post('name');
            $sort = request()->post('sort', 0);

            if ($name == null) $this->error(__('请完善热搜关键词信息！'));
            if (!is_numeric($sort)) $this->error(__('非法请求！'));

            $data = ['name' => $name, 'sort' => $sort];
            if (HotKeywordM::where('id', $ids)->update($data) !== false) {
                Cache::rm('hot_keyword_all');
                $this->success(__('Operation completed'));
            }
            $this->error(__('Operation failed'));
        }

        $this->view->assign('row', $row);
        return $this->view->fetch('edit');
    }

    /**
     * 删除
     */
    public function delete()
    {
        $ids = request()->post('ids');
        if (empty($ids))
            $this->error(__('Parameter exception'));
        $row = HotKeywordM::get($ids);
        if (!$row)
            $this->error(__('No Results were found'));

        if (HotKeywordM::where('id', 'in', $ids)->delete()) {
            Cache::rm('hot_keyword_all');
            $this->success(__('Operation completed'));
        }
        $this->error(__('Operation failed'));
    }

    /**
     * 修改排序
     */
    public function edit_sort()
    {
        $id = request()->post('id');
        $sort = request()->post('sort');

        if ($id == null || $sort == null || !is_numeric($sort)) {
            $this->error(__('Parameter exception'));
        }

        $row = HotKeywordM::get($id);
        if (!$row)
            $this->error(__('No Results were found'));

        if (HotKeywordM::where('id', $id)->setField('sort', $sort) !== false) {
            Cache::rm('hot_keyword_all');
            $this->success(__('Operation completed'));
        }
        $this->error(__('Operation failed'));
    }
}
