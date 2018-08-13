<?php
namespace app\admin\controller\indexs;

use think\Db;
use think\Exception;

use app\common\controller\Backend;
use app\common\model\feigo\IndexGroup;
use app\common\model\feigo\IndexGroupShopType;
use app\common\model\feigo\ShopTypes;

class Group extends Backend
{
    protected $relationSearch = true;
    protected $layout = 'default2';

    public function index()
    {
        $list_rows = $this->cur_limit;
        $list = (new IndexGroup)->field('*, id as group_id')->with('IndexGroupShopType')
            ->paginate($list_rows, false, ['query' => $this->request->get()]);

        $type_list = ShopTypes::getAllTypeToS();
        $this->view->assign('list', $list);
        $this->view->assign('type_list', $type_list);
        return $this->view->fetch();
    }

    /**
     * 新增分组
     * @return string
     */
    public function add()
    {
        if (request()->isAjax()) {
            $name = request()->post('name');
            $image_url = request()->post('image_url');

            if ($name == null || $image_url == null) {
                $this->error(__('请完善分组信息！'));
            }

            $data = [
                'name' => $name,
                'image_url' => $image_url,
                'is_show' => 0,
            ];
            if (IndexGroup::create($data)) {
                $this->success(__('Operation completed'));
            }
            $this->error(__('Operation failed'));
        }
        return $this->view->fetch('edit');
    }

    /**
     * 编辑分组
     * @param null $ids
     * @return string
     */
    public function edit($ids = null)
    {
        $row = IndexGroup::get($ids);
        if (!$row) $this->error(__('No Results were found'));

        if (request()->isAjax()) {
            $name = request()->post('name');
            $image_url = request()->post('image_url');

            if ($name == null || $image_url == null) {
                $this->error(__('请完善分组信息！'));
            }

            $data = [
                'name' => $name,
                'image_url' => $image_url,
            ];

            if (IndexGroup::where('id', $ids)->update($data) !== false) {
                $this->success(__('Operation completed'));
            }
            $this->error(__('Operation failed'));
        }

        $this->view->assign('row', $row);
        return $this->view->fetch('edit');
    }

    /**
     * 编辑分组状态
     */
    public function edit_status()
    {
        $ids = request()->post('ids');
        $is_show = request()->post('status');
        $row = IndexGroup::get($ids);

        if (empty($ids) || $is_show == null)
            $this->error(__('Parameter exception'));
        if (!$row)
            $this->error(__('No Results were found'));

        if (IndexGroup::where('id', 'in', $ids)->where('is_show', $is_show)->find()) {
            $this->error(__('有' . ($is_show == 1 ? '显示' : '隐藏') . '的分组，请重新选择！'));
        }

        if (IndexGroup::where('id', 'in', $ids)->setField('is_show', $is_show)) {
            $this->success(__('Operation completed'));
        }
        $this->error(__('Operation failed'));
    }

    /**
     * 删除分组
     */
    public function delete()
    {
        $ids = request()->post('ids');
        if (empty($ids))
            $this->error(__('Parameter exception'));
        $row = IndexGroup::get($ids);
        if (!$row)
            $this->error(__('No Results were found'));

        if (IndexGroup::where('id', 'in', $ids)->where('is_show', 1)->find()) {
            $this->error(__('有分组是显示状态，不能删除'));
        }
        Db::startTrans();
        try {
            IndexGroup::where('id', 'in', $ids)->delete();
            IndexGroupShopType::where('index_group_id', 'in', $ids)->delete();

            Db::commit();
            $this->success(__('Operation completed'));
        } catch (Exception $e) {
            Db::rollback();
        }
        $this->error(__('Operation failed'));
    }

    /**
     * 操作小组功能
     * @param null $ids
     * @return string
     * @throws \think\exception\DbException
     */
    public function edit_small($ids = null)
    {
        if (request()->isPost()) {
            $type_ids = request()->post('type_ids');
            $type_ids = explode(',', trim($type_ids, ','));
            if (empty($type_ids)) {
                $this->error(__('参数异常'));
            }
            $data = [];
            foreach ($type_ids as $v) {
                $data[] = ['index_group_id' => $ids, 'shop_type_id' => $v];
            }
            Db::startTrans();
            try {
                $m = new IndexGroupShopType;
                $m->where('index_group_id', $ids)->delete();
                $m->insertAll($data);
                Db::commit();
                $this->success(__('Operation completed'));
            } catch (Exception $e) {
                Db::rollback();
            }
            $this->error(__('Operation failed'));
        }
        $type_list = ShopTypes::where('is_show', 1)
            ->paginate($this->default_limit, false, ['query' => $this->request->get()]);

        $this->assign('type_list', $type_list);
        $this->assign('ids', $ids);
        return $this->view->fetch();
    }
}
