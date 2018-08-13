<?php
namespace app\admin\controller\sysconf;

use think\facade\Cache;

use app\common\controller\Backend;
use app\common\model\feigo\Goods;
use app\common\model\feigo\GoodTypes;

/**
 * 商品分类管理
 *
 * @icon fa fa-user
 */
class GoodType extends Backend
{
    protected $relationSearch = true;
    protected $layout = 'default2';

    /**
     * 列表
     * @return string|\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $tree = GoodTypes::getTreeAll();
        $total = GoodTypes::getDataTotal();
        $this->view->assign('list', $tree);
        $this->view->assign('total', $total);
        return $this->view->fetch();
    }

    /**
     * 新增
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add()
    {
        $pid = request()->param('pid');
        if (request()->isAjax()) {
            $name = request()->post('name');
            $icon = request()->post('icon');
            if (GoodTypes::where('name', $name)->where('pid', $pid)->find()) {
                $this->error(__('分类已存在'));
            }

            $data = [
                'name' => $name,
                'icon' => $icon,
                'pid' => $pid,
            ];
            if (GoodTypes::create($data)) {
                Cache::rm('good_types_all');
                Cache::rm('good_types_all_tree');
                $this->success(__('Operation completed'));
            }
            $this->error(__('Operation failed'));
        }
        $this->view->assign("pid", $pid);
        return $this->view->fetch('edit');
    }

    /**
     * 编辑
     * @param null $ids
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit($ids = null)
    {

        $row = GoodTypes::where(['id' => $ids])->find();
        if (!$row)
            $this->error(__('No Results were found'));
        if (request()->isAjax()) {
            $name = request()->post('name');
            $icon = request()->post('icon');

            if (GoodTypes::where('name', $name)->where('id', 'not in', $ids)->where('pid', $row['pid'])->find()) {
                $this->error(__('分类已存在'));
            }

            $data = [
                'name' => $name,
                'icon' => $icon,
            ];
            if (GoodTypes::where(['id' => $ids])->update($data) !== false) {
                Cache::rm('good_types_all');
                Cache::rm('good_types_all_tree');
                $this->success(__('Operation completed'));
            }
            $this->error(__('Operation failed'));
        }

        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 删除
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delete()
    {
        $ids = request()->post('ids');
        if (empty($ids))
            $this->error(__('Parameter exception'));

        $row = GoodTypes::where(['id' => $ids])->find();
        if (!$row)
            $this->error(__('No Results were found'));

        if (GoodTypes::where('pid', $ids)->find()) {
            $this->error(__('有下级分类，不能删除！'));
        }

        if (Goods::where('good_type_id', 'in', $ids)->find()) {
            $this->error(__('有关联的商品，请重新选择！'));
        }

        if (GoodTypes::where('id', 'in', $ids)->delete()) {
            Cache::rm('good_types_all');
            Cache::rm('good_types_all_tree');
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
        if (GoodTypes::where('id', $id)->setField('sort', $sort)) {
            Cache::rm('good_types_all');
            Cache::rm('good_types_all_tree');
            $this->success(__('Operation completed'));
        }
        $this->error(__('Operation failed'));
    }
}
