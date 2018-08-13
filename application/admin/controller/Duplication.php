<?php
namespace app\admin\controller;

use think\Db;
use app\common\controller\Backend;

use app\common\model\feigo\BrandShop;
use app\common\model\feigo\GoodShop;
use app\common\model\feigo\IndexShops;
use app\common\model\feigo\Regions;
use app\common\model\feigo\ShopAlbum;
use app\common\model\feigo\ShopComment;
use app\common\model\feigo\ShopDuplication;
use app\common\model\feigo\ShopPaperwork;
use app\common\model\feigo\Shops;
use app\common\model\feigo\ShopTypes;
use app\common\model\feigo\ShopTypeShop;

class Duplication extends Backend
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

        $name = request()->get('name');
        $region_id_3 = request()->get('region_id_3');
        $region_id_4 = request()->get('region_id_4');
        $status = request()->get('status', -1);
        $type_id = request()->get('type_id');
        $from_time = request()->get('from_time');

        $w = [];
        if ($name != null) $w[] = ['s.name', 'like', '%' . $name . '%'];
        if ($region_id_3 != null) $w[] = ['s.region_id_3', '=', $region_id_3];
        if ($region_id_4 != null) $w[] = ['s.region_id_4', '=', $region_id_4];
        if ($type_id != null) $w[] = ['t.type_id', '=', $type_id];
        $str = betweenTime($from_time, 's.create_time');
        if (!empty($str)) $w[] = $str;

        $shop_list = (new Shops)->alias('s')
            ->with('shopTypeShop')
            ->with('region3')
            ->with('region4')
            ->join('shop_duplication sd', 'sd.shop_id=s.id')
            ->join('shop_type_shop t', 't.shop_id=s.id')
            ->where($w)
            ->field('s.*, sd.group_id')
            ->group('s.id')
            ->paginate($list_rows, false, ['query' => $this->request->get()]);

        $type_list = ShopTypes::getAllTypeToS();
        $region_list_3 = Regions::where('parent_id', $city_id)->column('name', 'id');

        $this->view->assign('status', $status);
        $this->view->assign('list', $shop_list);
        $this->view->assign('type_list', $type_list);
        $this->view->assign('region_list_3', $region_list_3);
        return $this->view->fetch();
    }

    public function look($ids =null)
    {
        $a_fields = 'a.*, a.brand as brand_name';
        $b_fields = 'b.type_id';
        $c_fields = 'c.id as paperwork_id,c.name as truename,c.license_number,c.license_img,c.person_img_p,c.person_img_n,c.person_id_number, c.business_type';
        $d_fields = 'd.name as brand';
        $data = (new Shops())->alias('a')
            ->where('a.id', $ids)
            ->leftJoin('shop_type_shop b', 'b.shop_id=a.id')
            ->leftJoin('shop_paperwork c', 'c.shop_id=a.id')
            ->leftJoin('brands d', 'a.brand_id=d.id')
            ->join('regions r', 'r.id=a.region_id_3')
            ->field("$a_fields, $b_fields, $c_fields, $d_fields, r.name as region_name")
            ->find();

        // 附加数据
        $shop_types = (new ShopTypes())->where('is_show', 1)->field('id,name')->select();//门店类型列表
        $region_data = Regions::where('parent_id', $data['region_id_3'])->column('name', 'id');
        // 分类数据
        $typesArr = (new ShopTypeShop())
            ->alias('a')
            ->leftJoin('shop_types b', 'a.type_id=b.id')
            ->where('a.shop_id', $ids)
            ->field('a.type_id as id,b.name')
            ->select();

        $this->assign('city_id', $this->city_id);
        $this->assign('shop_types', $shop_types);
        $this->assign('region_data', $region_data);
        $this->assign('data', $data);
        $this->assign('typesData', $typesArr->toArray());

        return $this->view->fetch();
    }

    /**
     * 检测相似数据
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function check_data()
    {
        $city_id = $this->city_id;
        $city_name = Regions::where('id', $city_id)->value('name');
        $city_name = removeStr($city_name);
        $region_list_3 = Regions::where('parent_id', $city_id)->column('name', 'id');

        if (request()->isPost()) {
            $region_id_3 = request()->post('region_id_3');
            if ($region_id_3 == null) $this->error('参数错误');
            $qu_name = Regions::where('id', $region_id_3)->value('name');
            $qu_name = removeStr($qu_name);

            $shop_list = (new Shops)
                ->with('shopTypeShop')
                ->where('region_id_3', $region_id_3)
                ->select()->toArray();
            if (empty($shop_list)) $this->success('该区没有匹配到相似数据');

            $shop_count = count($shop_list);
            ShopDuplication::where('region_id_3', $region_id_3)->delete();
            for ($i = 0; $i < $shop_count; $i++) {
                if ($i == count($shop_list)) {
                    break;
                }
                $shop = isset($shop_list[$i]) ? $shop_list[$i] : '';
                if (empty($shop)) continue;
                unset($shop_list[$i]);

                $like_data = [];
                $group_id = ShopDuplication::order('group_id', 'desc')->value('group_id') + 1;
                foreach ($shop_list as $k => $v) {
                    // 距离
                    $distance = getDistance($v['latitude'], $v['longitude'], $shop['latitude'], $shop['longitude']);
                    if ($distance > 50) continue;
                    // 类型
                    $type_1 = $type_2 = [];
                    if (!empty($shop['shop_type_shop'])) {
                        foreach ($shop['shop_type_shop'] as $v2) {
                            $type_1[] = $v2['type_id'];
                        }
                    }
                    if (!empty($v['shop_type_shop'])) {
                        foreach ($v['shop_type_shop'] as $v2) {
                            $type_2[] = $v2['type_id'];
                        }
                    }
                    $type = array_intersect($type_1, $type_2);
                    if (count($type) < 1) continue;
                    // 地址
                    similar_text(removeStr($v['address'], $city_name, $qu_name), removeStr($shop['address'], $city_name, $qu_name), $proportion);
                    if ($proportion < 50) continue;

                    $like_data[] = ['shop_id' => $v['id'], 'group_id' => $group_id, 'region_id_3' => $region_id_3];
                    unset($shop_list[$k]);
                }

                if (!empty($like_data)) {
                    array_unshift($like_data, ['shop_id' => $shop['id'], 'group_id' => $group_id, 'region_id_3' => $region_id_3]);
                    (new ShopDuplication)->insertAll($like_data);
                }
            }
            $this->success('数据检查结束');
        }

        $this->view->assign('region_list_3', $region_list_3);
        return $this->view->fetch();
    }

    /**
     * 删除相似数据 门店删除
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delete()
    {
        $ids = request()->post('ids');
        $group_id = request()->post('group_id');

        if (empty($ids) || empty($group_id)) $this->error('请求错误');
        $shop = ShopDuplication::where('shop_id', $ids)->where('group_id', $group_id)->find();
        if (empty($shop)) $this->error('请求错误');

        $shop = Shops::where('id', $ids)->find();
        if (empty($shop)) $this->error('已删除');

        if ($shop['is_user_status'] == 1) $this->error('该门店已被认领，不可删除');

        Db::startTrans();
        try {
            // 删除
            Shops::where('id', 'in', $ids)->delete();

            // 删除首页推荐
            IndexShops::where('id', 'in', $ids)->delete();

            // 删除门店评论
            ShopComment::where('shop_id', 'in', $ids)->setField('shop_id', 0);

            // 删除门店法人信息
            ShopPaperwork::where('shop_id', 'in', $ids)->delete();

            // 删除门店相册
            ShopAlbum::where('shop_id', 'in', $ids)->delete();

            // 删除门店分类关联数据
            ShopTypeShop::where('shop_id', 'in', $ids)->delete();

            // 删除门店品牌关联数据
            BrandShop::where('shop_id', 'in', $ids)->delete();

            // 删除相似数据
            ShopDuplication::where('shop_id', $ids)->where('group_id', $group_id)->delete();

            // 删除门店商品数据
            $goods_id = GoodShop::where('shop_id', 'in', $ids)->column('good_id');
            GoodShop::where('shop_id', 'in', $ids)->delete();
            if ($goods_id){
                // 删除商品
                Goods::where('id', 'in', $goods_id)->delete();

                // 删除图片集
                GoodImages::where('good_id', 'in', $goods_id)->delete();

                // 删除商品详情图片
                GoodDetailImages::where('good_id', 'in', $goods_id)->delete();
            }
            Db::commit();
            $this->success('操作成功');
        } catch (Exception $e) {
            Db::rollback();
            $this->error('删除失败');
        }
    }

    /**
     * 排除数据
     */
    public function exclude_data()
    {
        $ids = request()->post('ids');
        $group_id = request()->post('group_id');

        if (empty($ids) || empty($group_id)) $this->error('请求错误');
        ShopDuplication::where('shop_id', $ids)->where('group_id', $group_id)->delete();

        $this->success('排除成功');
    }
}
