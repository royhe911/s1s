<?php
namespace app\admin\controller\indexs;

use think\Db;
use think\Exception;

use app\common\controller\Backend;
use app\common\model\feigo\Adverts;
use app\common\model\feigo\IndexSpecial;
use app\common\model\feigo\ShopTypes;

class Special extends Backend
{
    protected $relationSearch = true;
    protected $layout = 'default2';

    public function index()
    {
        $list_rows = $this->cur_limit;
        $list = (new IndexSpecial())->alias('s')
            ->join('shop_types t', 's.shop_type_id=t.id')
            ->field('s.*, t.name as type_name')
            ->order('sort')
            ->paginate($list_rows, false, ['query' => $this->request->get()]);

        $this->view->assign('list', $list);
        return $this->view->fetch();
    }

    /**
     * 新增专题
     * @return string
     */
    public function add()
    {
        if (request()->isAjax()) {
            $name = request()->post('name');
            $shop_type_id = request()->post('shop_type_id');
            $is_show_ad = request()->post('is_show_ad');

            if ($name == null || $shop_type_id == null || $is_show_ad == null) {
                $this->error(__('请完善专题信息！'));
            }

            $data = [
                'name' => $name,
                'shop_type_id' => $shop_type_id,
                'is_show_ad' => $is_show_ad,
                'is_show' => 0,
                'sort' => 0,
            ];
            if (IndexSpecial::create($data)) {
                $this->success(__('Operation completed'));
            }
            $this->error(__('Operation failed'));
        }
        $type_list = ShopTypes::getAllTypeToS();
        $this->view->assign('type_list', $type_list);
        return $this->view->fetch('edit');
    }

    /**
     * 编辑专题
     * @param null $ids
     * @return string
     */
    public function edit($ids = null)
    {
        $row = IndexSpecial::get($ids);
        if (!$row) $this->error(__('No Results were found'));

        if (request()->isAjax()) {
            $name = request()->post('name');
            $shop_type_id = request()->post('shop_type_id');
            $is_show_ad = request()->post('is_show_ad');

            if ($name == null || $shop_type_id == null || $is_show_ad == null) {
                $this->error(__('请完善专题信息！'));
            }

            $data = [
                'name' => $name,
                'shop_type_id' => $shop_type_id,
                'is_show_ad' => $is_show_ad,
            ];

            Db::startTrans();
            try {
                if ($is_show_ad != 1) { // 隐藏广告位，删除广告
                    $w[] = ['device', '=', 4]; // pc
                    $w[] = ['position', '=', 1]; // 首页
                    $w[] = ['sub_id', '=', $ids]; // 子模块
                    Adverts::where($w)->delete();
                }
                IndexSpecial::where('id', $ids)->update($data);
                Db::commit();
                $this->success(__('Operation completed'));
            } catch (Exception $e) {
                Db::rollback();
            }
            $this->error(__('Operation failed'));
        }
        $type_list = ShopTypes::getAllTypeToS();
        $this->view->assign('type_list', $type_list);
        $this->view->assign('row', $row);
        return $this->view->fetch('edit');
    }

    /**
     * 编辑专题状态
     */
    public function edit_status()
    {
        $ids = request()->post('ids');
        $is_show = request()->post('status');
        $row = IndexSpecial::get($ids);

        if (empty($ids) || $is_show == null)
            $this->error(__('Parameter exception'));
        if (!$row)
            $this->error(__('No Results were found'));

        if (IndexSpecial::where('id', 'in', $ids)->where('is_show', $is_show)->find()) {
            $this->error(__('有' . ($is_show == 1 ? '显示' : '隐藏') . '的专题，请重新选择！'));
        }
        Db::startTrans();
        try {
            if ($is_show != 1) { // 隐藏广告位，删除广告
                $w[] = ['device', '=', 4]; // pc
                $w[] = ['position', '=', 1]; // 首页
                $w[] = ['sub_id', '=', $ids]; // 子模块
                Adverts::where($w)->setField('is_show', $is_show);
            }
            IndexSpecial::where('id', 'in', $ids)->setField('is_show', $is_show);
            Db::commit();
            $this->success(__('Operation completed'));
        } catch (Exception $e) {
            Db::rollback();
        }
        $this->error(__('Operation failed'));
    }

    /**
     * 删除专题
     */
    public function delete()
    {
        $ids = request()->post('ids');
        if (empty($ids))
            $this->error(__('Parameter exception'));
        $row = IndexSpecial::get($ids);
        if (!$row)
            $this->error(__('No Results were found'));

        if (IndexSpecial::where('id', 'in', $ids)->where('is_show', 1)->find()) {
            $this->error(__('有专题是显示状态，不能删除'));
        }
        Db::startTrans();
        try {
            $w[] = ['device', '=', 4]; // pc
            $w[] = ['position', '=', 1]; // 首页
            $w[] = ['sub_id', '=', $ids]; // 子模块
            Adverts::where($w)->delete();
            IndexSpecial::where('id', 'in', $ids)->delete();
            Db::commit();
            $this->success(__('Operation completed'));
        } catch (Exception $e) {
            Db::rollback();
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
        if (IndexSpecial::where('id', $id)->setField('sort', $sort) !== false) {
            $this->success(__('Operation completed'));
        }
        $this->error(__('Operation failed'));
    }
}
