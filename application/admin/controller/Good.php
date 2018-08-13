<?php
namespace app\admin\controller;

use app\common\model\feigo\GoodTypes;
use think\Db;
use think\Exception;

use app\common\controller\Backend;
use app\common\model\feigo\GoodDetailImages;
use app\common\model\feigo\GoodImages;
use app\common\model\feigo\GoodImport;
use app\common\model\feigo\Goods;
use app\common\model\feigo\GoodShop;
use app\common\model\feigo\IndexGoods;
use app\common\model\feigo\Regions;
use app\common\model\feigo\ShopbyGoodTypes;
use app\common\model\feigo\Shops;

class Good extends Backend
{
    protected $relationSearch = true;
    protected $layout = 'default2';

    /**
     * 列表
     * @return string|\think\response\Json
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $city_id = $this->city_id;
        $list_rows = $this->cur_limit;

        $good_name = request()->get('good_name');
        $shop_name = request()->get('shop_name');
        $status = request()->get('status', -1);
        $from_time = request()->get('from_time');

        $w[] = ['s.region_id_2', '=', $city_id];
        $w[] = ['g.is_delete', '=', 0];
        $w[] = ['g.is_shop', '=', 1];

        if ($good_name != null && !empty($good_name)) $w[] = ['g.title', 'like', '%' . $good_name . '%'];
        if ($shop_name != null && !empty($shop_name)) $w[] = ['s.name', 'like', '%' . $shop_name . '%'];
        if ($status >= 0) $w[] = ['g.status', '=', $status];
        $str = betweenTime($from_time, 'g.create_time');
        if (!empty($str)) $w[] = $str;

        $list = (new Goods)->alias('g')
            ->join('good_shop gs', 'gs.good_id=g.id')
            ->join('shops s', 'gs.shop_id=s.id')
            ->join('index_goods i', 'i.id=g.id', 'left')
            ->field('g.id, g.title, g.price, g.preview_image, g.brand, g.status, s.name, i.id is_index')
            ->order('g.create_time desc')
            ->where($w)->group('g.id')
            ->paginate($list_rows, false, ['query' => $this->request->get()]);

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
            $shop_id = request()->post('shop_id');
            $brand = request()->post('brand_id');
            $type_id = request()->post('type_id');
            $product_id = request()->post('product_id');
            $price = request()->post('price', null);
            $title = request()->post('title');
            $image = request()->post('image');
            $desc = htmlspecialchars_decode(request()->post('desc'));

            if ($shop_id == null || $type_id == null || $product_id == null || $image == null || $title == null) {
                $this->error(__('请完善商品信息！'));
            }
            $image_arr = json_decode(htmlspecialchars_decode($image), true);
            if (!is_array($image_arr) || empty($image_arr)) $this->error(__('请上传商品图片！'));

            if ($desc != null) {
                $desc_url = getImgUrl($desc);
                if (empty($desc_url)) $this->error(__('请使用编辑器传图片！'));
            }

            $time = date('Y-m-d H:i:s', time());
            $data = [
                'brand' => $brand,
                'good_type_id' => $type_id,
                'product_id' => $product_id,
                'price' => $price,
                'title' => $title,
                'is_shop' => 1,
                'status' => 0,
                'preview_image' => $image_arr[0],
                'create_time' => $time
            ];

            Db::startTrans();
            try {
                $good_id = (new Goods)->insertGetId($data);
                $data = [];
                foreach ($desc_url as $v) {
                    $url = str_replace(config('qiniu.domain'), '', $v);
                    $data[] = ['good_id' => $good_id, 'detail_image' => $url, 'create_time' => $time];
                }
                (new GoodDetailImages)->insertAll($data);

                $data = [
                    'good_id' => $good_id,
                    'shop_id' => $shop_id,
                ];
                GoodShop::create($data);

                $data = [];
                foreach ($image_arr as $v) {
                    $data[] = ['good_id' => $good_id, 'goods_image' => $v, 'create_time' => $time];
                }

                Shops::where('id', $shop_id)->setInc('good_count');

                (new GoodImages())->insertAll($data);
                Db::commit();
                $this->success(__('Operation completed'));
            } catch (Exception $e) {
                Db::rollback();
                $this->error(__('Operation failed'));
            }
        }
        $pt_good_type = GoodTypes::getTypeListHtml();

        $this->view->assign('pt_good_type', $pt_good_type);
        return $this->view->fetch('edit');
    }

    /**
     * 编辑
     * @param null $ids
     * @return string
     */
    public function edit($ids = null)
    {
        $goods_db = new Goods();
        $bool = $goods_db->where('id', $ids)->where('status', 1)->field('id')->find();
        if ($bool) $this->error(__('启用状态的数据不可编辑！'));

        if (request()->isAjax()) {
            $shop_id = request()->post('shop_id');
            $brand = request()->post('brand_id');
            $type_id = request()->post('type_id');
            $product_id = request()->post('product_id');
            $price = request()->post('price');
            $title = request()->post('title');
            $image = request()->post('image');
            $desc = htmlspecialchars_decode(request()->post('desc'));
            if ($shop_id == null || $type_id == null || $product_id == null || $image == null || $title == null) $this->error(__('请完善商品信息！'));

            $image_arr = json_decode(htmlspecialchars_decode($image), true);
            if (!is_array($image_arr) || empty($image_arr)) $this->error(__('请上传商品图片！'));

            if ($desc != null) {
                $desc_url = getImgUrl($desc);
                if (empty($desc_url)) {
                    $this->error(__('请使用编辑器传图片！'));
                }
            }

            $time = date('Y-m-d H:i:s', time());
            $data = [
                'brand' => $brand,
                'good_type_id' => intval($type_id),
                'product_id' => intval($product_id),
                'price' => $price,
                'title' => $title,
                'is_shop' => 1,
                'preview_image' => $image_arr[0],
                'create_time' => $time
            ];

            Db::startTrans();
            try {
                $goods_db->where('id', $ids)->update($data);
                // 删除商品详情图片
                GoodDetailImages::where('good_id', $ids)->delete();
                $data = [];
                foreach ($desc_url as $v) {
                    $url = str_replace(config('qiniu.domain'), '', $v);
                    $data[] = ['good_id' => $ids, 'detail_image' => $url, 'create_time' => $time];
                }
                (new GoodDetailImages)->insertAll($data);

                // 删除门店关联
                GoodShop::where('good_id', $ids)->delete();
                $data = [
                    'good_id' => $ids,
                    'shop_id' => $shop_id,
                ];
                GoodShop::create($data);

                // 删除图片集
                GoodImages::where('good_id', $ids)->delete();
                $data = [];
                foreach ($image_arr as $v) {
                    $data[] = ['good_id' => $ids, 'goods_image' => $v, 'create_time' => $time];
                }
                (new GoodImages())->insertAll($data);
                Db::commit();
                $this->success(__('Operation completed'));
            } catch (Exception $e) {
                Db::rollback();
                $this->error(__('Operation failed'));
            }
        }

        $row = $goods_db->alias('g')
            ->join('good_shop gs', 'gs.good_id=g.id')
            ->with('GoodImages')
            ->with('goodDetailImages')
            ->join('shops s', 'gs.shop_id=s.id')
            ->field('g.id, g.title, g.price, g.good_type_id, g.product_id, g.preview_image, g.status, s.name, s.id shop_id, g.brand, s.is_user_status')
            ->where('g.id', $ids)
            ->find();

        if (empty($row['good_images'])) {
            $row['good_images'][0] = ['goods_image' => $row['preview_image']];
        }

        $html = '';
        if (!empty($row['good_detail_images'])) {
            foreach ($row['good_detail_images'] as $k => $v) {
                $html .= '<p><img src="' . config('qiniu.domain') . $v['detail_image'] . '" style="" title=""/></p>';
            }
        }
        $brand = Shops::where('id', $row['shop_id'])->value('brand');
        $brand_list = [];
        if (!empty($brand)) {
            $brand_list = explode(',', $brand);
        }
        if ($row['is_user_status'] == 1) {
            $good_type = ShopbyGoodTypes::getTypeListHtml(0, $row['shop_id'], $row['good_type_id'], false, true, 0);
        } else {
            $good_type = ShopbyGoodTypes::getTypeListHtml(0, 0, $row['good_type_id'], false, true, 0);
        }
        $pt_good_type = GoodTypes::getTypeListHtml(0, $row['product_id']);

        $this->view->assign('row', $row);
        $this->view->assign('html', $html);
        $this->view->assign('good_type', $good_type);
        $this->view->assign('pt_good_type', $pt_good_type);
        $this->view->assign('brand_list', $brand_list);
        return $this->view->fetch('edit');
    }

    /**
     * 门店列表
     * @return string
     */
    public function shop_list()
    {
        $city_id = $this->city_id;
        $shop_name = request()->get('shop_name');
        $region_id_3 = request()->get('region_id_3');
        $region_id_4 = request()->get('region_id_4');
        $list_rows = request()->get('list_rows');
        $list_rows = empty($list_rows) ? 10 : $list_rows;

        $w[] = ['region_id_2', '=', $city_id];
        $w[] = ['status', '=', 1];
        if ($shop_name != null) $w[] = ['name', 'like', '%' . $shop_name . '%'];
        if ($region_id_3 != null) $w[] = ['region_id_3', '=', $region_id_3];
        if ($region_id_4 != null) $w[] = ['region_id_4', '=', $region_id_4];
        $shop_list = (new Shops)->alias('s')
            ->with('region3')
            ->with('region4')
            ->where($w)->paginate($list_rows, false, ['query' => $this->request->get()]);

        $region_list_3 = Regions::where('parent_id', $city_id)->column('name', 'id');
        $this->view->assign('shop_list', $shop_list);
        $this->view->assign('region_list_3', $region_list_3);
        return $this->view->fetch();
    }

    /**
     * 品牌列表
     */
    public function brand_list()
    {
        $shop_id = request()->get('shop_id');
        if (empty($shop_id) || $shop_id == null)
            $this->error(__('缺少参数'));

        $shop = Shops::get($shop_id);
        if (empty($shop)) return $this->result(null, 1, '没有找到门店', 'json');

        // 品牌
        $brand = $shop['brand'];
        $data = $brand_list = [];
        if (!empty($brand)) $brand_list = explode(',', $brand);

        $data['brand_list'] = $brand_list;

        // 分类
        if ($shop['is_user_status'] == 1) {
            $good_type = ShopbyGoodTypes::getTypeListHtml(0, $shop_id, 0, false, true, 0);
        } else {
            $good_type = ShopbyGoodTypes::getTypeListHtml(0, 0, 0, false, true, 0);
        }
        if (empty($good_type)) {
            return $this->result($data, 0, '请先添加门店下的商品分类', 'json');
        }
        $data['good_type'] = $good_type;
        return $this->result($data, 1, '', 'json');
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
            if (IndexGoods::where('id', 'in', $ids)->find()) {
                $this->error(__('有商品已推荐到首页，不能下架'));
            }
        } else {
            $w[] = ['gs.good_id', 'in', $ids];
            $w[] = ['s.status', '=', 0];
            $list = (new GoodShop)->alias('gs')->join('shops s', 's.id=gs.shop_id')
                ->where($w)->find();
            if ($list) {
                $this->error(__('有商品所属的门店已禁用，不能上架商品'));
            }
        }

        if (Goods::where('id', 'in', $ids)->where('status', $status)->find()) {
            $this->error(__('有' . ($status == 1 ? '上架' : '下架') . '的商品，请重新选择！'));
        }

        if (Goods::where('id', 'in', $ids)->setField('status', $status)) {
            $this->success(__('Operation completed'));
        }
        $this->error(__('Operation failed'));
    }

    /**
     * 推荐到首页
     */
    public function edit_index()
    {
        $ids = request()->post('ids');
        $status = request()->post('status');
        if (empty($ids) || $status == null)
            $this->error(__('Parameter exception'));
        if ($status == 0) {
            if (IndexGoods::where('id', 'in', $ids)->delete()) {
                $this->success(__('Operation completed'));
            }
        } elseif ($status == 1) {
            if (IndexGoods::where('id', 'in', $ids)->where('region_id_2', $this->city_id)->find()) {
                $this->error(__('该城市已经推荐了选择的商品。请重新选择'));
            }
            if (Goods::where('id', 'in', $ids)->where('status', 0)->find()) {
                $this->error(__('有下架的商品。不能推荐到首页'));
            }
            $data = [
                'id' => $ids,
                'region_id_2' => $this->city_id,
            ];
            if (IndexGoods::create($data)) {
                $this->success(__('Operation completed'));
            }
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

        if (Goods::where('id', 'in', $ids)->where('status', 1)->find()) {
            $this->error(__('有商品未下架，不能删除'));
        }

        if (IndexGoods::where('id', 'in', $ids)->find()) {
            $this->error(__('有商品已推荐到首页，不能删除'));
        }
        $shop_id = (new GoodShop)->where('good_id', 'in', $ids)->column('shop_id');

        if (Goods::where('id', 'in', $ids)->delete()) {

            //删除门店关联
            GoodShop::where('good_id', 'in', $ids)->delete();

            // 删除图片集
            GoodImages::where('good_id', 'in', $ids)->delete();

            // 删除商品详情图片
            GoodDetailImages::where('good_id', 'in', $ids)->delete();

            Shops::where('id', 'in', $shop_id)->setDec('good_count');

            $this->success(__('Operation completed'));
        }

        $this->error(__('Operation failed'));
    }

    /**
     * 上传商品的导入文件
     */
    public function upload_good_file()
    {
        if (request()->isPost()) {
            $shop_id = request()->param('shop_id');
            $file = request()->file('file');
            $branch_no = request()->param('branch_no');
            if (empty($branch_no) || !is_numeric($branch_no) || empty($file) || empty($shop_id)) {
                return $this->result([], 0, '请求错误', 'json');
            }
            $file = $file->getInfo();
            if (!isset($file['type']) || !in_array($file['type'], ['application/octet-stream', 'application/x-zip', 'application/zip', 'application/x-zip-compressed'])) {
                return $this->result([], 0, '请上传zip格式文件', 'json');
            }
            $prefix = config('qiniu.file_prefix') . $branch_no . '/';
            $name = $prefix . base_convert(time() * 1000, 10, 36) . "_" . base_convert(microtime(), 10, 36) . uniqid() . '.zip';
            $token = \Qiniu::token(3600, config('qiniu.private_bucket'));
            $res = \Qiniu::instance()->uploadOne($file['tmp_name'], $prefix, $name, $token);
            if (empty($res) || empty($res['key'])) {
                $this->error(__('文件上传失败'));
            }
            $member = $this->auth->getUserInfo();
            $data = [
                'branch_no' => $branch_no,
                'url' => $res['key'],
                'shop_id' => $shop_id,
                'member_id' => $member['id'],
                'create_time' => date('Y-m-d H:i:s', time()),
            ];
            if (GoodImport::create($data)) {
                $this->result([], 1, '文件上传成功，请等待服务器对数据进行处理', 'json');
            }
            $this->result([], 0, '', 'json');
        }
        $branch_no = GoodImport::getNewBranch();
        $this->view->assign('branch_no', $branch_no);
        return $this->view->fetch();
    }

    /**
     * 根据输入的门店关键字查找门店列表
     */
    public function find_shop_list()
    {
        $keyword = request()->param('keyword');

        if (empty($keyword) || $keyword == null) {
            return $this->result([], 0);
        }
        $w[] = ['region_id_2', '=', $this->city_id];
        $w[] = ['status', '=', 1];
        $w[] = ['name', 'like', $keyword];
        $shop_list = Shops::where($w)->column('name', 'id');
        return $this->result($shop_list, 1);
    }
}
