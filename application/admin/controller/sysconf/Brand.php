<?php
namespace app\admin\controller\sysconf;

use app\common\controller\Backend;
use app\common\model\feigo\Brands;
use app\common\model\feigo\Goods;

/**
 * 品牌管理
 *
 * @icon fa fa-user
 */
class Brand extends Backend
{

    protected $relationSearch = true;
    protected $layout = 'default2';

    /**
     * 查看
     */
    public function index()
    {
        $keyword = request()->get('keyword');
        $status = request()->get('status', -1);
        $list_rows = $this->cur_limit;

        $w = [];
        if ($keyword != null && !empty($keyword)) $w[] = ['name', 'like', '%' . $keyword . '%'];
        if ($status >= 0) $w[] = ['status', '=', $status];

        $list = Brands::where($w)->order('id')->paginate($list_rows, false, ['query' => $this->request->get()]);
        $this->view->assign('list', $list);
        $this->view->assign('status', $status);
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
            $logo = request()->post('logo');
            $desc = request()->post('desc');
            if (Brands::where('name', $name)->find()) {
                $this->error(__('品牌已存在'));
            }

            $data = [
                'name' => $name,
                'logo' => $logo,
                'desc' => $desc,
                'create_time' => date('Y-m-d H:i:s', time())
            ];
            if (Brands::create($data)) {
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

        $row = Brands::where(['id' => $ids])->find();
        if (!$row) $this->error(__('No Results were found'));

        if ($row['status'] == 1) $this->error(__('启用状态的数据不可编辑！'));

        if (request()->isAjax()) {
            $name = request()->post('name');
            $logo = request()->post('logo');
            $desc = request()->post('desc');
            if (Brands::where('name', $name)->where('id', 'not in', $ids)->find()) {
                $this->error(__('品牌已存在'));
            }

            $data = [
                'name' => $name,
                'logo' => $logo,
                'desc' => $desc,
            ];
            if (Brands::where(['id' => $ids])->update($data) !== false) {
                $this->success(__('Operation completed'));
            }
            $this->error(__('Operation failed'));
        }

        $this->view->assign("row", $row);
        return $this->view->fetch();
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
        if ($status == 0) { // 禁用前需要查看是否商品都删除了
            if (Goods::where('brand_id', 'in', $ids)->find()) {
                $this->error(__('有品牌下还有商品没有处理，不能禁用'));
            }
        }

        if (Brands::where('id', 'in', $ids)->where('status', $status)->find()) {
            $this->error(__('有' . ($status == 1 ? '启用' : '禁用') . '的品牌，请重新选择！'));
        }

        if (Brands::where('id', 'in', $ids)->setField('status', $status)) {
            $this->success(__('Operation completed'));
        }
        $this->error(__('Operation failed'));
    }

    /**
     * 删除
     * @Author: zhuhaijun
     * @Date: 2018/4/12
     */
    public function delete()
    {
        $ids = request()->post('ids');

        if (empty($ids)) $this->error(__('Parameter exception'));

        if (Goods::where('brand_id', 'in', $ids)->find()) {
            $this->error(__('有品牌下还有商品没有处理，不能删除'));
        }

        if (Brands::where('id', 'in', $ids)->delete()) {
            $this->success(__('Operation completed'));
        }

        $this->error(__('Operation failed'));
    }

}
