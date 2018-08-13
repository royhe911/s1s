<?php
namespace app\admin\controller\sysconf;

use app\common\controller\Backend;
use app\common\model\feigo\Goods;
use app\common\model\feigo\IndexGoods;
use app\common\model\feigo\Regions;
use app\common\model\feigo\Shops;
use app\common\model\feigo\ShopTypes;

/**
 * 商品推荐
 *
 * @icon fa fa-user
 */
class IndexGood extends Backend
{
    protected $relationSearch = true;
    protected $layout = 'default2';

    /**
     * 查看
     */
    public function index()
    {
        $city_id = $this->city_id;

        $good_name = request()->get('good_name');
        $shop_name = request()->get('shop_name');
        $region_id_3 = request()->get('region_id_3');
        $region_id_4 = request()->get('region_id_4');
        $list_rows = $this->cur_limit;

        $w[] = ['s.region_id_2', '=', $city_id];
        $w[] = ['g.is_delete', '=', 0];
        $w[] = ['g.is_shop', '=', 1];
        if ($good_name != null && !empty($good_name)) $w[] = ['g.title', 'like', '%' . $good_name . '%'];
        if ($shop_name != null && !empty($shop_name)) $w[] = ['s.name', 'like', '%' . $shop_name . '%'];
        if ($region_id_3 != null && !empty($region_id_3)) $w[] = ['s.region_id_3', '=', $region_id_3];
        if ($region_id_4 != null && !empty($region_id_4)) $w[] = ['s.region_id_4', '=', $region_id_4];


        $list = (new Shops())->alias('s')
            ->with('region3')
            ->with('region4')
            ->join('good_shop gs', 'gs.shop_id=s.id')
            ->join('goods g', 'g.id=gs.good_id')
            ->join('index_goods i', 'i.id=g.id')
            ->field('g.*, s.name shop_name, i.sort, s.id shop_id, s.region_id_3, s.region_id_4')
            ->where($w)
            ->order('i.sort', 'asc')
            ->paginate($list_rows, false, ['query' => $this->request->get()]);

        $region_list_3 = Regions::where('parent_id', $this->city_id)->column('name', 'id');
        $this->view->assign('list', $list);
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
            $good_ids = request()->post('good_ids');
            if (empty($good_ids) || $good_ids == null) {
                $this->error(__('请选择要推荐的商品'));
            }
            $good_id_arr = explode(',', trim($good_ids, ','));
            if (!is_array($good_id_arr)) {
                $this->error(__('请求错误'));
            }
            $data = [];
            $i = 1;
            foreach ($good_id_arr as $v) {
                $data[$i]['id'] = $v;
                $data[$i]['region_id_2'] = $this->city_id;
                $i++;
            }
            if ((new IndexGoods)->insertAll($data)) {
                $this->success(__('Operation completed'));
            }
            $this->error(__('Operation failed'));
        }

        $this->goodList();
        $type_list = ShopTypes::getAllType();
        $region_list_3 = Regions::where('parent_id', $this->city_id)->column('name', 'id');
        $this->view->assign('type_list', $type_list);
        $this->view->assign('region_list_3', $region_list_3);
        return $this->view->fetch('edit');
    }

    /**
     * ajax获取商品列表
     */
    private function goodList()
    {
        $region_id_3 = request()->param('region_id_3');
        $region_id_4 = request()->param('region_id_4');
        $type_id = request()->param('type_id');
        $list_rows = request()->param('list_rows');
        $name = request()->param('name');
        $list_rows = empty($list_rows) ? 10 : $list_rows;

        $w[] = ['s.region_id_2', '=', $this->city_id];
        $w[] = ['s.status', '=', 1];
        $w[] = ['g.status', '=', 1];
        $w[] = ['g.is_shop', '=', 1];
        $w[] = ['b.status', '=', 1];

        $w2[] = ['s.region_id_2', '=', $this->city_id];
        $w2[] = ['s.status', '=', 1];

        if ($name != null && !empty($name)) {
            $w[] = ['s.name', 'like', "%$name%"];
        }
        if ($region_id_3 != null && !empty($region_id_3)) {
            $w[] = ['s.region_id_3', '=', $region_id_3];
            $w2[] = ['s.region_id_3', '=', $region_id_3];
        }
        if ($region_id_4 != null && !empty($region_id_4)) {
            $w[] = ['s.region_id_4', '=', $region_id_4];
            $w2[] = ['s.region_id_4', '=', $region_id_4];
        }
        if ($type_id != null && !empty($type_id)) {
            $w[] = ['t.type_id', '=', $type_id];
            $w2[] = ['t.type_id', '=', $type_id];
        }

        $shop_list = (new Shops())->alias('s')
            ->join('shop_type_shop t', 't.shop_id=s.id')
            ->join('good_shop gs', 'gs.shop_id=s.id')
            ->where($w2)
            ->column('s.name', 's.id');

        $list = (new Goods)->alias('g')
            ->join('good_shop gs', 'gs.good_id=g.id')
            ->join('shops s', 'gs.shop_id=s.id')
            ->join('brands b', 'b.id=g.brand_id')
            ->join('shop_type_shop t', 't.shop_id=s.id')
            ->field('g.id, g.title, g.price, g.preview_image, g.status, s.name, b.name brand_name')
            ->where($w)
            ->where('g.id', 'not in', function ($query) {
                $query->table('feigo_index_goods')->field('id')->select();
            })
            ->group('g.id')
            ->limit($list_rows)
            ->paginate($list_rows, false, ['query' => $this->request->get()]);

        $this->view->assign('shop_list', $shop_list);
        $this->view->assign('list', $list);
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

        $row = IndexGoods::get($id);
        if (!$row)
            $this->error(__('No Results were found'));

        if (IndexGoods::where('id', $id)->setField('sort', $sort)) {
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
        if (IndexGoods::where('id', 'in', $ids)->delete()) {
            $this->success(__('Operation completed'));
        }
        $this->error(__('Operation failed'));
    }
}
