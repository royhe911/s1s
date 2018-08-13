<?php
namespace app\admin\controller\sysconf;

use app\common\controller\Backend;
use app\common\model\feigo\ShopTypes;
use app\common\model\feigo\ShopTypeShop;
use think\facade\Cache;

/**
 * 门店分类管理
 *
 * @icon fa fa-user
 */
class ShopType extends Backend
{
    protected $relationSearch = true;
    protected $layout = 'default2';

    /**
     * 查看
     */
    public function index()
    {
        $list = ShopTypes::getAllType();
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
            $icon = request()->post('icon');
            if (ShopTypes::where('name', $name)->find()) {
                $this->error(__('分类已存在'));
            }

            $data = [
                'name' => $name,
                'icon' => $icon,
                'create_time' => date('Y-m-d H:i:s', time())
            ];
            if (ShopTypes::create($data)) {
                Cache::rm('shop_types_all');
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
        $row = ShopTypes::where(['id' => $ids])->find();
        if (!$row)
            $this->error(__('No Results were found'));
        if (request()->isAjax()) {
            $name = request()->post('name');
            $icon = request()->post('icon');

            if (ShopTypes::where('name', $name)->where('id', 'not in', $ids)->find()) {
                $this->error(__('分类已存在'));
            }

            $data = [
                'name' => $name,
                'icon' => $icon,
            ];
            if (ShopTypes::where(['id' => $ids])->update($data) !== false) {
                Cache::rm('shop_types_all');
                $this->success(__('Operation completed'));
            }
            $this->error(__('Operation failed'));
        }

        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 删除
     */
    public function delete()
    {
        $ids = request()->post('ids');
        if (empty($ids))
            $this->error(__('Parameter exception'));

        if (ShopTypeShop::where('type_id', 'in', $ids)->find()) {
            $this->error(__('有关联的的门店，请重新选择！'));
        }

        if (ShopTypes::where('id', 'in', $ids)->delete()) {
            Cache::rm('shop_types_all');
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

        if ($id == null || $sort == null) {
            $this->error(__('Parameter exception'));
        }
        if (ShopTypes::where('id', $id)->setField('sort', $sort)) {
            Cache::rm('shop_types_all');
            $this->success(__('Operation completed'));
        }
        $this->error(__('Operation failed'));
    }
}
