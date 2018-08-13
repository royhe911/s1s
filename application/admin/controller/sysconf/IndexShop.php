<?php
namespace app\admin\controller\sysconf;

use app\common\controller\Backend;
use app\common\model\feigo\IndexShops;
use app\common\model\feigo\Regions;
use app\common\model\feigo\Shops;
use app\common\model\feigo\ShopTypes;

/**
 * 门店推荐
 *
 * @icon fa fa-user
 */
class IndexShop extends Backend
{
    protected $relationSearch = true;
    protected $layout = 'default2';

    /**
     * 查看
     */
    public function index()
    {
        $city_id = $this->city_id;

        $shop_name = request()->get('shop_name');
        $region_id_3 = request()->get('region_id_3');
        $region_id_4 = request()->get('region_id_4');
        $list_rows = $this->cur_limit;

        $w[] = ['s.region_id_2', '=', $city_id];
        if ($shop_name != null && !empty($shop_name)) $w[] = ['s.name', 'like', '%' . $shop_name . '%'];
        if ($region_id_3 != null && !empty($region_id_3)) $w[] = ['s.region_id_3', '=', $region_id_3];
        if ($region_id_4 != null && !empty($region_id_4)) $w[] = ['s.region_id_4', '=', $region_id_4];

        $list = (new Shops())->alias('s')
            ->with('region3')
            ->with('region4')
            ->with('shopTypeShop')
            ->join('index_shops i', 'i.id=s.id')
            ->field('s.*, i.sort')
            ->where($w)
            ->order('i.sort', 'asc')
            ->paginate($list_rows, false, ['query' => $this->request->get()]);

        $region_list_3 = Regions::where('parent_id', $this->city_id)->column('name', 'id');
        $type_list = ShopTypes::getAllType();
        $this->view->assign('list', $list);
        $this->view->assign('type_list', $type_list);
        $this->view->assign('region_list_3', $region_list_3);
        return $this->view->fetch();
    }

    /**
     * 新增
     * @return string
     */
    public function add()
    {
        if (request()->isAjax()) {
            $shop_ids = request()->post('shop_ids');
            if (empty($shop_ids)) $this->error(__('请选择要推荐的门店'));
            $shop_id_arr = explode(',', trim($shop_ids, ','));
            if (!is_array($shop_id_arr) || empty($shop_id_arr)) $this->error(__('请求错误'));

            $db = new IndexShops();

            if (count($shop_id_arr) > 1) {
                $data = [];
                foreach ($shop_id_arr as $value) $data[] = ['id' => $value];
                $bool = $db->insertAll($data);
            } else {
                $data = ['id' => $shop_id_arr[0]];
                $bool = $db->insert($data);
            }

            if (!$bool) $this->error(__('Operation failed'));

            $this->success(__('Operation completed'));
        }

        $this->shopList();

        $type_list = ShopTypes::getAllType();
        $region_list_3 = Regions::where('parent_id', $this->city_id)->column('name', 'id');
        $this->view->assign('type_list', $type_list);
        $this->view->assign('region_list_3', $region_list_3);
        return $this->view->fetch('edit');
    }

    /**
     * ajax获取门店列表
     */
    public function shopList()
    {
        $region_id_3 = request()->param('region_id_3');
        $region_id_4 = request()->param('region_id_4');
        $type_id = request()->param('type_id');
        $name = request()->param('name');
        $list_rows = request()->param('list_rows');
        $list_rows = empty($list_rows) ? 16 : $list_rows;

        $w[] = ['s.region_id_2', '=', $this->city_id];
        $w[] = ['s.is_delete', '=', 0];
        $w[] = ['s.status', '=', 1];
        if ($name != null && !empty($name)) $w[] = ['s.name', 'like', "%$name%"];
        if ($region_id_3 != null && !empty($region_id_3)) $w[] = ['s.region_id_3', '=', $region_id_3];
        if ($region_id_4 != null && !empty($region_id_4)) $w[] = ['s.region_id_4', '=', $region_id_4];
        if ($type_id != null && !empty($type_id)) $w[] = ['t.type_id', '=', $type_id];

        $list = (new Shops())->alias('s')
            ->with('region3')
            ->with('region4')
            ->with('shopTypeShop')
            ->join('shop_type_shop t', 't.shop_id=s.id')
            ->field('s.*')
            ->where($w)
            ->where('s.id', 'not in', function ($query) {
                $query->table('feigo_index_shops')->field('id')->select();
            })
            ->group('s.id')
            ->paginate($list_rows, false, ['query' => $this->request->get()]);

        $this->assign('list', $list);
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

        $row = IndexShops::get($id);
        if (!$row)
            $this->error(__('No Results were found'));

        if (IndexShops::where('id', $id)->setField('sort', $sort)) {
            $this->success(__('Operation completed'));
        }
        $this->error(__('Operation failed'));
    }

    /**
     * 取消推荐
     */
    public function delete()
    {
        $ids = request()->post('ids');
        if (empty($ids))
            $this->error(__('Parameter exception'));
        if (IndexShops::where('id', 'in', $ids)->delete()) {
            $this->success(__('Operation completed'));
        }
        $this->error(__('Operation failed'));
    }
}
