<?php
namespace app\admin\controller;

use app\common\model\feigo\BusinessMessage;
use think\Db;
use think\Exception;
use think\facade\Request;

use app\common\controller\Backend;
use app\common\model\feigo\Brands;
use app\common\model\feigo\BrandShop;
use app\common\model\feigo\Goods;
use app\common\model\feigo\GoodShop;
use app\common\model\feigo\GoodDetailImages;
use app\common\model\feigo\GoodImages;
use app\common\model\feigo\IndexGoods;
use app\common\model\feigo\IndexShops;
use app\common\model\feigo\Regions;
use app\common\model\feigo\ShopAlbum;
use app\common\model\feigo\ShopComment;
use app\common\model\feigo\ShopPaperwork;
use app\common\model\feigo\Shops as ShopsModel;
use app\common\model\feigo\ShopTypes;
use app\common\model\feigo\ShopTypeShop;
use app\common\model\feigo\ShopImport;
use app\common\model\feigo\BusinessShop;
use app\common\model\feigo\ShopCommentReply;

class Shops extends Backend
{
    protected $relationSearch = true;
    protected $layout = 'default2';

    protected $device = [
        1 => 'Android',  2 => 'IOS', 3 => '小程序', 4 => 'PC'
    ];

    protected $business_type = [
        1 => '自营', 2 => '代理', 3 => '直营', 4 => '加盟'
    ];

    protected $week = [
        '1' => '周一', '2' => '周二', '3' => '周三', '4' => '周四', '5' => '周五', '6' => '周六', '7' => '周日',
    ];

    /**
     * 门店列表
     * @return string|\think\response\Json
     * @throws \Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $list_rows = $this->cur_limit;
        $city_id = $this->city_id;
        $name = request()->get('name');
        $status = request()->get('status', -1);
        $is_promotion = request()->get('is_promotion', -1);
        $data_source = request()->get('data_source');
        $is_user_status = request()->get('is_user_status', -1);
        $is_vip = request()->get('is_vip');
        $region_id_3 = request()->get('region_id_3');
        $region_id_4 = request()->get('region_id_4');
        $from_time = request()->get('from_time');
        $type_id = request()->get('type_id');
        $shop_id = request()->get('shop_id');

        $where[] = ['s.is_delete', '=', 0];
        $where[] = ['s.region_id_2', '=', $city_id];
        if (!empty($name)) $where[] = ['s.name', 'like', "%$name%"];
        if (!empty($shop_id)) $where[] = ['s.id', '=', $shop_id];
        if ($status >= 0) $where[] = ['s.status', '=', $status];//状态
        if ($is_promotion >= 0) $where[] = ['s.is_promotion', '=', $is_promotion];
        if ($data_source > 0) $where[] = ['s.data_source', '=', $data_source];
        if ($is_user_status >= 0) $where[] = ['s.is_user_status', '=', $is_user_status];
        if ($is_vip > 0) $where[] = ['s.is_vip', '=', $is_vip];

        if (!empty($region_id_3)) $where[] = ['s.region_id_3', '=', $region_id_3];//区
        if (!empty($region_id_4)) $where[] = ['s.region_id_4', '=', $region_id_4];//商圈
        if (!empty($type_id)) $where[] = ['sts.type_id', '=', $type_id];//门店类型

        //创建时间段
        $str = betweenTime($from_time, 's.create_time');
        if (!empty($str)) $where[] = $str;

        $list = (new ShopsModel())->alias('s')
            ->with('shopTypeShop')
            ->with('region_3')
            ->with('region_4')
            ->join('shop_type_shop sts', 'sts.shop_id=s.id')
            ->join('business_shop bs', 'bs.shop_id=s.id', 'left')
            ->join('business_member bm', 'bm.id=bs.business_id', 'left')
            ->join('index_shops i', 'i.id=s.id', 'left')
            ->where($where)
            ->field('s.id, s.name, s.score, s.address, s.contacts, s.data_source, s.is_user_status, s.doorplate, s.status, s.phone_number, s.is_promotion, s.comment_count, s.comment_hidden_count, s.good_count, bm.nickname, i.id is_index, s.region_id_3, s.region_id_4')
            ->order('s.id desc')->group('s.id')
            ->paginate($list_rows, false, ['query' => $this->request->get()]);

        $type_list = ShopTypes::getAllTypeToS();
        $region_list_3 = Regions::where('parent_id', $city_id)->column('name', 'id');

        $this->assign('list', $list);
        $this->assign('type_list', $type_list);
        $this->assign('region_list_3', $region_list_3);
        $this->assign('city_id', $city_id);
        $this->assign('status', $status);
        $this->assign('is_promotion', $is_promotion);
        $this->assign('is_user_status', $is_user_status);

        return $this->view->fetch();
    }

    /**
     * 新增门店
     * @return string
     * @throws \Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function add_shop()
    {
        if (request()->isPost()) {
            $shop_info = [
                'name' => request()->post('name'),
                'desc' => request()->post('desc'),
                'type_ids' => request()->post('type_ids'),
                'region_id_3' => request()->post('region_id_3'),
                'region_id_4' => request()->post('region_id_4'),
                'address' => request()->post('address'),
                'doorplate' => request()->post('doorplate'),
                'brand' => request()->post('brand'),
                'preview_image' => request()->post('preview_image'),
                'preview_images' => request()->post('preview_images'),
                'acreage' => request()->post('acreage'),
                'scale' => request()->post('scale'),
                'contacts' => request()->post('contacts'),
                'phone_number' => request()->post('phone_number'),
                'phone_number1' => request()->post('phone_number1'),
                'business_type' => request()->post('business_type'),
                'business_status' => request()->post('business_status'),
                'region_id_1' => Regions::getPreId($this->city_id),
                'region_id_2' => $this->city_id,
                'create_time' => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
                'update_time' => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
                'brand_id' => 0,
                'status' => 0,
                'data_source' => 3
            ];
            $coordinate = request()->post('coordinate');
            if (empty($coordinate)) $this->success(__('没有找到门店经纬度'));
            $coordinate = explode(',', $coordinate);
            if (!isset($coordinate[0]) || !isset($coordinate[1])) {
                $this->success(__('没有找到门店经纬度'));
            }
            $shop_info['longitude'] = $coordinate[0];
            $shop_info['latitude'] = $coordinate[1];

            $business_hours_new = request()->post('business_hours_new');
            $business_hours_new = htmlspecialchars_decode($business_hours_new);
            $business_hours_new = json_decode($business_hours_new);
            $business_hours = [];
            foreach ($business_hours_new as $v) {
                if (!isset($v[0]) || !isset($v[1])) {
                    $this->error(__('数据格式有误··'));
                    break;
                }
                $week = trim($v[0], ',');
                $week = explode(',', $week);
                $weeks = [];
                foreach ($week as $v2) {
                    $weeks[$v2] = $v2;
                }
                $business_hours[] = ['week' => $weeks, 'time' => $v[1]];
            }
            $shop_info['business_hours_new'] = json_encode($business_hours);
            if (ShopsModel::checkShopData($shop_info) == false) {
                $this->error(__('缺失参数'));
            }
            $paperwork_info = [
                'license_status' => request()->post('license_status'),
                'license_type' => request()->post('license_type'),
                'license_name' => request()->post('license_name'),
                'license_img' => request()->post('license_img'),
                'license_number' => request()->post('license_number'),
                'name' => request()->post('true_name'),
                'banksite' => request()->post('banksite'),
                'bankcard' => request()->post('bankcard'),
                'person_img_p' => request()->post('person_img_p'),
                'person_img_n' => request()->post('person_img_n'),
                'person_img_s' => request()->post('person_img_s'),
                'business_type' => request()->post('business_type'),
                'person_id_number' => request()->post('person_id_number'),
            ];

            $type_ids = trim($shop_info['type_ids'], ',');
            $preview_images = trim($shop_info['preview_images'], ',');

            $type_ids = explode(',', $type_ids);
            $preview_images = explode(',', $preview_images);
            unset($shop_info['type_ids'], $shop_info['preview_images']);

            $shop_id = (new ShopsModel)->insertGetId($shop_info);
            if (!$shop_id) $this->error(__('服务器忙，请稍后··'));

            // 增加证书数据
            $paperwork_info['shop_id'] = $shop_id;
            (new ShopPaperwork())->insert($paperwork_info);

            // 增加分类数据
            $add_data = [];
            foreach ($type_ids as $value) $add_data[] = ['shop_id' => $shop_id, 'type_id' => $value];
            (new ShopTypeShop())->insertAll($add_data);

            // 加入相册
            $add_data = [];
            foreach ($preview_images as $value) $add_data[] = ['shop_id' => $shop_id, 'image_url' => $value];
            (new ShopAlbum())->insertAll($add_data);

            $this->success(__('Operation completed'));
        }

        // 附加数据
        $shop_types = (new ShopTypes())->where('is_show', 1)->field('id,name')->select();//门店类型列表
        $brands = (new Brands())->where('status', 1)->field('id,name')->select();

        $this->assign('city_id', $this->city_id);
        $this->assign('shop_types', $shop_types);
        $this->assign('brands', $brands);
        $this->assign('week', $this->week);
        $this->assign('has_auth', true);
        $this->assign('business_type', $this->business_type);

        return $this->view->fetch('edit_shop');
    }

    /**
     * 单一 编辑门店
     * @return string
     * @throws \Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit_shop()
    {
        $id = intval(request()->request('id'));
        $has_auth = $this->auth->check('dashboard/certified');

        if (empty($id) || $id < 1) $this->error(__('非法操作！'));

        $shop = ShopsModel::where('id',  $id)->find();
        if ($shop['status'] == 1) $this->error(__('启用状态的数据不可编辑！'));

        if (request()->isPost()) {
            Db::startTrans();
            try {
                $shop_info = [
                    'name' => request()->post('name'),
                    'desc' => request()->post('desc'),
                    'type_ids' => request()->post('type_ids'),
                    'region_id_3' => request()->post('region_id_3'),
                    'region_id_4' => request()->post('region_id_4'),
                    'address' => request()->post('address'),
                    'doorplate' => request()->post('doorplate'),
                    'brand' => request()->post('brand'),
                    'preview_image' => request()->post('preview_image'),
                    'preview_images' => request()->post('preview_images'),
                    'acreage' => request()->post('acreage'),
                    'scale' => request()->post('scale'),
                    'contacts' => request()->post('contacts'),
                    'phone_number' => request()->post('phone_number'),
                    'phone_number1' => request()->post('phone_number1'),
                    'business_type' => request()->post('business_type'),
                    'business_status' => request()->post('business_status'),
                    //'update_time' => date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']),
                ];
                $business_hours_new = request()->post('business_hours_new');
                $business_hours_new = htmlspecialchars_decode($business_hours_new);
                $business_hours_new = json_decode($business_hours_new);
                $business_hours = [];
                foreach ($business_hours_new as $v) {
                    if (!isset($v[0]) || !isset($v[1])) {
                        $this->error(__('数据格式有误··'));
                        break;
                    }
                    $week = trim($v[0], ',');
                    $week = explode(',', $week);
                    $weeks = [];
                    foreach ($week as $v2) {
                        $weeks[$v2] = $v2;
                    }
                    $business_hours[] = ['week' => $weeks, 'time' => $v[1]];
                }
                $shop_info['business_hours_new'] = json_encode($business_hours);
                $paperwork_info = [
                    'license_type' => request()->post('license_type'),
                    'license_name' => request()->post('license_name'),
                    'license_img' => request()->post('license_img'),
                    'license_number' => request()->post('license_number'),
                    'name' => request()->post('true_name'),
                    'banksite' => request()->post('banksite'),
                    'bankcard' => request()->post('bankcard'),
                    'person_img_p' => request()->post('person_img_p'),
                    'person_img_n' => request()->post('person_img_n'),
                    'person_img_s' => request()->post('person_img_s'),
                    'business_type' => request()->post('business_type'),
                    'person_id_number' => request()->post('person_id_number'),
                ];

                $type_ids = $shop_info['type_ids'];
                $preview_images = $shop_info['preview_images'];

                $type_ids = explode(',', $type_ids);
                $preview_images = explode(',', $preview_images);
                unset($shop_info['type_ids'], $shop_info['preview_images']);

                // 编辑门店
                (new ShopsModel)->where('id', $id)->update($shop_info);

                // 编辑证书数据
                if ($shop['is_user_status'] != 1 || $has_auth) {
                    (new ShopPaperwork())->where('shop_id', $id)->update($paperwork_info);
                }

                // 删除分类
                (new ShopTypeShop())->where('shop_id', $id)->delete();
                // 增加分类数据
                $add_data = [];
                foreach ($type_ids as $value) $add_data[] = ['shop_id' => $id, 'type_id' => $value];
                (new ShopTypeShop())->insertAll($add_data);

                // 删除相册
                (new ShopAlbum())->where('shop_id', $id)->delete();
                // 加入相册
                $add_data = [];
                foreach ($preview_images as $value) $add_data[] = ['shop_id' => $id, 'image_url' => $value];
                (new ShopAlbum())->insertAll($add_data);
                Db::commit();
                $this->success(__('Operation completed'));
            } catch (Exception $e) {

                Db::rollback();
                $this->error(__($e->getMessage()));
            }
        }
        $row = (new ShopsModel())->alias('a')
            ->with('shopTypeShop')
            ->with('shopAlbum')
            ->with('region_1')
            ->with('region_2')
            ->with('region_3')
            ->with('region_4')
            ->where('a.id', $id)
            ->leftJoin('shop_paperwork c', 'c.shop_id=a.id')
            ->field("a.*, c.id as paperwork_id, c.name as truename, c.license_number, c.license_img, c.person_img_p, c.person_img_n, c.person_img_s, c.person_id_number, c.business_type, c.banksite, c.bankcard, c.license_name, c.license_type")
            ->find();

        // 附加数据
        $shop_type = (new ShopTypes())->where('is_show', 1)->column('name', 'id');//门店类型列表
        $region = Regions::column('name', 'id');
        $region_list_4 = Regions::where('parent_id', $row['region_id_3'])->column('name', 'id');
        $shop_type_arr = [];
        if (!empty($row['shop_type_shop'])) {
            foreach ($row['shop_type_shop'] as $k => $v) {
                $shop_type_arr[] = ['id' => $v['type_id'], 'name' => isset($shop_type[$v['type_id']]) ? $shop_type[$v['type_id']] : ''];
            }
        }
        $row['phone_number1'] = explode('-', $row['phone_number1']);
        $business_hours = [];
        if (!empty($row['business_hours_new'])) {
            $business_hours_new = json_decode($row['business_hours_new'], true);
            if (!empty($business_hours_new)) {
                foreach ($business_hours_new as $v) {
                    $week = '';
                    foreach ($v['week'] as $v2) {
                        $week .= $v2 . ',';
                    }
                    $business_hours[] = ['week' => $week, 'time' => $v['time']];
                }
            }
        }

        $this->assign('business_type', $this->business_type);
        $this->assign('has_auth', $has_auth);
        $this->assign('row', $row);
        $this->assign('city_id', $this->city_id);
        $this->assign('shop_type', $shop_type);
        $this->assign('region', $region);
        $this->assign('week', $this->week);
        $this->assign('business_hours', $business_hours);
        $this->assign('business_type', $this->business_type);
        $this->assign('region_list_4', $region_list_4);
        $this->assign('shop_type_arr', $shop_type_arr);
        $this->assign('shop_type_json', empty($shop_type_arr) ? '' : json_encode($shop_type_arr));
        return $this->view->fetch();
    }

    /**
     * ajax 编辑门店状态
     */
    public function shop_status()
    {
        if (!request()->isAjax()) $this->error(__('错误的操作方式！'));

        Db::startTrans();

        try {
            $id = trim(request()->post('id'), ',');
            $status = request()->post('status');

            if (empty($id)) throw new Exception('非法操作！');
            if ($status != 1 && $status != 0) throw new Exception('非法操作！');

            $shops_db = new ShopsModel();

            //判断状态
            $bool = $shops_db->where('status', $status)->where('id', 'in', $id)->find('id');
            if ($bool) throw new Exception('有' . ($status == 1 ? '启用': '停用') . '的门店，请重新选择！');

            if ($status == 0){
                //推荐判断
                $db = new IndexShops();
                $bool = $db->where('id', 'in', $id)->find();
                if ($bool) throw new Exception('门店正在推荐中，请在推荐结束后停用！');

                //推荐商品判断
                $db = new IndexGoods();
                $bool = $db->alias('a')
                    ->where('b.shop_id', 'in', $id)
                    ->join('good_shop b', 'a.id=b.good_id')
                    ->find();
                if ($bool) throw new Exception('门店有商品正在推荐中，请在推荐结束后停用！');
            }

            //更新状态
            $bool = $shops_db->where('id', 'in', $id)->update(['status' => $status]);
            if ($bool === false) throw new Exception('服务器忙，请稍后··');

            //关联商品下架
            if($status == 0){
                $db = new Goods();
                $bool = $db
                    ->alias('a')
                    ->leftJoin('good_shop b', 'a.id=b.good_id')
                    ->where('b.shop_id', 'in', $id)
                    ->update(['status' => 0]);
            }

            Db::commit();
            $this->success(__('Operation completed'));
        } catch (Exception $e) {
            Db::rollback();

            $this->error(__($e->getMessage()));
        }
    }

    /**
     * ajax 删除门店
     */
    public function delete_shop()
    {
        if (!request()->isAjax()) $this->error(__('错误的操作方式！'));

        Db::startTrans();
        try {
            $id = trim(request()->post('id'), ',');

            if (empty($id)) throw new Exception('非法操作！');

            //推荐判断
            $db = new IndexShops();
            $bool = $db->where('id', 'in', $id)->find();
            if ($bool) throw new Exception('有门店正在推荐中，请在推荐结束后再删除！');


            //推荐商品判断
            $db = new IndexGoods();
            $bool = $db->alias('a')
                ->where('b.shop_id', 'in', $id)
                ->join('good_shop b', 'a.id=b.good_id')
                ->find();
            if ($bool) throw new Exception('门店有商品正在推荐中，请在推荐结束后删除！');

            //删除
            $db = new ShopsModel();
            $bool = $db->where('id', 'in', $id)->where('status', 1)->find();

            if ($bool) throw new Exception('请停用门店后删除！');

            $bool = $db->where('id', 'in', $id)->delete();

            if (!$bool) throw new Exception('服务器忙，请稍后··');

            //删除门店评论
            $db = new ShopComment();
            $bool = $db->where('shop_id', 'in', $id)->update(['shop_id' => 0]);

            //删除门店法人信息
            $db = new ShopPaperwork();
            $bool = $db->where('shop_id', 'in', $id)->delete();

            //删除门店相册
            $db = new ShopAlbum();
            $bool = $db->where('shop_id', 'in', $id)->delete();

            //删除门店分类关联数据
            $db = new ShopTypeShop();
            $bool = $db->where('shop_id', 'in', $id)->delete();

            //删除门店品牌关联数据
            $db = new BrandShop();
            $bool = $db->where('shop_id', 'in', $id)->delete();

            //删除门店商品数据
            $db = new GoodShop();
            $list = $db->where('shop_id', 'in', $id)->field('good_id')->select();
            $bool = $db->where('shop_id', 'in', $id)->delete();
            if ($list){
                $list = $list->toArray();
                $db = new Goods();
                $goods_id = implode(',', array_column($list, 'good_id'));
                // 删除商品
                $bool = $db->where('id', 'in', $goods_id)->delete();

                // 删除图片集
                $bool = GoodImages::where('good_id', 'in', $goods_id)->delete();

                // 删除商品详情图片
                $bool = GoodDetailImages::where('good_id', 'in', $goods_id)->delete();
            }

            Db::commit();

            $this->success(__('Operation completed'));
        } catch (Exception $e) {
            Db::rollback();
            $this->error(__($e->getMessage()));
        }
    }

    /**
     * 门店评论列表
     * @return string
     * @throws \Exception
     * @throws \think\exception\DbException
     */
    public function comments()
    {
        $list_rows = $this->cur_limit;
        $city_id = $this->city_id;

        $key_word = request()->get('key_word');//关键字
        $nickname = request()->get('nickname');//昵称
        $shop_name = request()->get('shop_name');//门店名称
        $region_id_3 = request()->get('region_id_3');
        $region_id_4 = request()->get('region_id_4');
        $from_time = request()->get('from_time');
        $score = request()->get('score');
        $status = request()->get('status', -1); // 是否回复 1-是 0-否
        $device = request()->get('device'); // 设备来源

        $where[] = ['s.is_delete', '=', 0];
        $where[] = ['s.region_id_2', '=', $city_id];//市
        if (!empty($key_word)) $where[] = ['sc.content', 'like', "%$key_word%"];//关键字
        if (!empty($nickname)) $where[] = ['sc.nickname', 'like', "%$nickname%"];//用户昵称
        if (!empty($score)) {
            $where[] = ['sc.score', 'egt', $score];
            $where[] = ['sc.score', 'lt', $score . '.99'];
        }

        if ($status >= 0 && $status != null) $where[] = ['sc.is_reply', '=', $status];
        if (!empty($device)) $where[] = ['sc.device', '=', $device];

        //创建时间段
        $str = betweenTime($from_time, 'sc.create_time');
        if (!empty($str)) $where[] = $str;
        if (!empty($shop_name)) $where[] = ['s.name', 'like', "%$shop_name%"];//商铺名称
        if (!empty($region_id_3)) $where[] = ['s.region_id_3', '=', $region_id_3];//区
        if (!empty($region_id_4)) $where[] = ['s.region_id_4', '=', $region_id_4];//商圈

        $list = (new ShopComment())
            ->alias('sc')
            ->leftJoin('shops s', 'sc.shop_id=s.id')
            ->where($where)
            ->field('sc.id, sc.nickname, sc.score, sc.device, sc.is_delete, sc.create_time, sc.is_reply, s.name as shop_name, s.region_id_3, s.region_id_4')
            ->order('sc.create_time desc')
            ->paginate($list_rows, false, ['query' => $this->request->get()]);

        //附加数据
        $region_data = Regions::getTreeAll();
        $region_list_3 = Regions::where('parent_id', $city_id)->column('name', 'id');

        $this->assign('region_list_3', $region_list_3);
        $this->assign('status', $status);
        $this->assign('city_id', $this->city_id);
        $this->assign('device', $this->device);
        $this->assign('region_data', $region_data);
        $this->assign('list', $list);
        return $this->view->fetch();
    }

    /**
     * 门店评论详情
     * @return string
     * @throws \Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function comment()
    {
        $id = intval(request()->get('id'));
        if (empty($id) || $id < 1) $this->error(__('非法操作！'));

        $data = (new ShopComment())->alias('a')
            ->leftJoin('shops b', 'a.shop_id=b.id')
            ->where('a.id', $id)
            ->field('a.*,b.name as shop_name')
            ->find();
        $reply = [];
        if ($data['is_reply'] == 1) {
            $reply = ShopCommentReply::where('comment_id', $data['id'])->find();
        }

        $this->assign('reply', $reply);
        $this->assign('data', $data);
        return $this->view->fetch();
    }

    /**
     * 编辑门店评论
     * @return string
     * @throws \Exception
     */
    public function edit_comment()
    {
        if (request()->isPost()) {

            $id = intval(request()->post('id'));

            if (empty($id) || $id < 1) $this->error(__('非法操作！'));

            $post_data = request()->post();
            $is_empty = (
                (isset($post_data['shop_id']) && empty($post_data['shop_id'])) ||
                (isset($post_data['member_id']) && empty($post_data['member_id'])) ||
                (isset($post_data['nickname']) && empty($post_data['nickname'])) ||
                (isset($post_data['score']) && empty($post_data['score'])) ||
                (isset($post_data['content']) && empty($post_data['content']))
            );
            $is_empty && $this->error(__('请完善门店信息！'));

            $bool = (new ShopComment())->where('id', $id)->update($post_data);
            $bool ? $this->success(__('Operation completed')) : $this->error(__('服务器忙，请稍后··'));
        } else {
            return $this->view->fetch();
        }
    }

    /**
     * ajax 删除门店评论
     */
    public function delete_comment()
    {
        if (!request()->isAjax()) $this->error(__('错误的操作方式！'));

        $id = $this->request->param('id');

        if (empty($id)) $this->error(__('非法操作！'));

        $shop_comment_m = new ShopComment();
        $w[] = ['is_delete', '=', 0];
        $w[] = ['id', '=', $id];
        $shop_id = $shop_comment_m->where($w)->value('shop_id');
        if (empty($shop_id)) $this->error(__('非法操作！'));

        Db::startTrans();
        try {
            $shop_comment_m->where('id', $id)->setField('is_delete', 1);
            $shop_show = $shop_comment_m->field('count(shop_id) comment_count, AVG(score) comment_score')->where('is_delete', 0)->where('shop_id', $shop_id)->find();
            $shop_hidden = $shop_comment_m->where('is_delete', 1)->where('shop_id', $shop_id)->count('shop_id');

            $comment_score = round($shop_show['comment_score'],1) ?? 0;
            ShopsModel::update([
                'comment_count' => $shop_show['comment_count'],
                'comment_hidden_count' => $shop_hidden,
                'score' => $comment_score
            ], [
                'id' => $shop_id
            ]);

            Db::startTrans();
            $this->success(__('Operation completed'));
        } catch (Exception $e) {
            Db::rollback();
            $this->error(__('服务器忙，请稍后··'));
        }
    }

    /**
     * ajax 门店推荐
     * id 多个使用,号隔开
     * status >0:推荐操作 ? other:关闭推荐操作
     */
    public function shop_index()
    {
        if (!request()->isAjax()) $this->error(__('错误的操作方式！'));

        try {

            $id = trim(request()->post('id'), ',');
            $status = intval(request()->post('status'));// 推荐状态

            if (empty($id) || !isset($status)) throw new Exception('非法操作!');

            $db = new IndexShops();

            if ($status <= 0) {
                $bool = $db->where('id', 'in', $id)->delete();
            } else {
                //推荐判断
                $bool = (new ShopsModel())->where('id', 'in', $id)->where('status', 0)->field('id')->find();

                if ($bool) throw new Exception('您要推荐的门店为禁止状态不可以推荐！');

                $bool = $db->where('id', 'in', $id)->field('id')->find();

                if ($bool) throw new Exception('您要推荐的门店已为推荐状态！');

                $idArr = explode(',', $id);

                if (count($idArr) > 1) {
                    $data = [];
                    foreach ($idArr as $value) $data[] = ['id' => $value];
                    $bool = $db->insertAll($data);
                } else {
                    $data = ['id' => $idArr[0]];
                    $bool = $db->insert($data);
                }
            }

            if (!$bool) throw new Exception('服务器忙，请稍后··');

            $this->success(__('Operation completed'));
        } catch (Exception $e) {
            $this->error(__($e->getMessage()));
        }
    }

    /**
     * 门店品牌
     * @return string
     * @throws \Exception
     * @throws \think\exception\DbException
     */
    public function brands()
    {
        $shop_id = intval(request()->request('shop_id'));
        $list_rows = $this->default_limit;

        $db = new Brands();
        $where = [];
        $brand_name = request()->get('brand_name');
        if (!empty($brand_name)) $where[] = ['name', 'like', "%{$brand_name}%"];
        $brands = $db->where($where)
            ->paginate($list_rows, false, ['query' => $this->request->get()]);

        $this->assign('brands', $brands);
        $this->assign('shop_id', $shop_id);

        return $this->view->fetch();
    }

    /**
     * 门店分类
     * @return string
     * @throws \Exception
     * @throws \think\exception\DbException
     */
    public function types()
    {
        $shop_id = intval(request()->request('shop_id'));
        $list_rows = $this->default_limit;

        $db = new ShopTypes();
        $where = [];
        $type_name = request()->get('type_name');
        if (!empty($type_name)) $where[] = ['name', 'like', "%{$type_name}%"];
        $types = $db->where($where)
            ->paginate($list_rows, false, ['query' => $this->request->get()]);

        $this->assign('types', $types);
        $this->assign('shop_id', $shop_id);
        return $this->view->fetch();
    }

    /**
     * 门店相册管理(包含更新)
     * @return string
     * @throws \Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function album()
    {
        $shop_id = intval(request()->request('shop_id'));

        if (empty($shop_id) || $shop_id < 1) $this->error(__('非法操作！'));

        $db = new ShopAlbum();
        if (request()->isPost()) {
            //更新操作
            $data = Request::param();
            $data = isset($data['data']) ? $data['data'] : [];
            if (empty($data) || !is_array($data)) $this->error(__('请输入正常的数据!'));

            $db->where('shop_id', $shop_id)->delete();
            $bool = $db->insertAll($data);

            if (!$bool) $this->error(__('服务器忙，请稍后··'));
            $this->success(__('Operation completed'));
        }
        $list = $db->where('shop_id', $shop_id)->select();

        $this->assign('shop_id', $shop_id);
        $this->assign('list', $list);
        return $this->view->fetch();
    }

    /**
     * ajax新增门店相册图片
     */
    public function add_album()
    {
        if (!request()->isAjax()) $this->error(__('错误的操作方式！'));
        $shop_id = intval(request()->post('shop_id'));//状态
        if (empty($shop_id) || $shop_id < 1) $this->error(__('非法操作！'));

        $post_data = request()->post();

        if (!($id = (new ShopAlbum)->insertGetId($post_data))) $this->error(__('服务器忙，请稍后··'));
        $this->success($id);
    }

    /**
     * ajax门店相册编辑
     */
    public function edit_album()
    {
        if (!request()->isAjax()) $this->error(__('错误的操作方式！'));

        $shop_id = intval(request()->post('shop_id'));//状态

        if (empty($shop_id) || $shop_id < 1) $this->error(__('非法操作！'));

        $updateData = request()->post('data');

        $db = new ShopAlbum();
        $db->where('shop_id', $shop_id)->delete();
        $bool = $db->insertAll($updateData);

        if (!$bool) $this->error(__('服务器忙，请稍后··'));

        $this->success(__('Operation completed'));
    }

    /**
     * ajax 删除门店图片
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function delete_album()
    {
        if (!request()->isAjax()) $this->error(__('错误的操作方式！'));

        $id = intval(request()->get('id'));

        if (empty($id) || $id < 1) $this->error(__('非法操作！'));

        $db = new ShopAlbum();
        $data = $db->where('id', $id)->find();
        $bool = $db->where('id', $id)->delete();

        if (is_file($data['image_url'])) @unlink($data['image_url']);//删除文件

        $bool ? $this->success(__('Operation completed')) : $this->error(__('服务器忙，请稍后··'));
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
        $w[] = ['name', 'like', '%' . $keyword . '%'];
        $shop_list = (new ShopsModel())->where($w)->column('name', 'id');
        return $this->result($shop_list, 1);
    }

    /**
     * 上传门店的导入文件
     */
    public function upload_shop_file()
    {
        if (request()->isPost()) {
            $file = request()->file('file');
            $branch_no = request()->param('branch_no');
            if (empty($branch_no) || !is_numeric($branch_no) || empty($file)) {
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
                'region_id_2' => $this->city_id,
                'member_id' => $member['id'],
                'create_time' => date('Y-m-d H:i:s', time()),
            ];
            if (ShopImport::create($data)) {
                $this->result([], 1, '文件上传成功，请等待服务器对数据进行处理', 'json');
            }
            $this->result([], 0, '', 'json');
        }
        $branch_no = ShopImport::getNewBranch();
        $this->view->assign('branch_no', $branch_no);
        return $this->view->fetch();
    }

    /**
     * 认领待审核列表
     * @return string
     * @throws \think\exception\DbException
     */
    public function pending_shop()
    {
        $list_rows = $this->cur_limit;
        $city_id = $this->city_id;
        $name = request()->get('name');
        $region_id_3 = request()->get('region_id_3');
        $region_id_4 = request()->get('region_id_4');
        $type_id = request()->get('type_id');
        $from_time = request()->get('from_time');

        $where[] = ['bs.status', 'in', [0, 2]];
        $where[] = ['bs.region_id_2', '=', $city_id];
        if (!empty($name)) $where[] = ['bs.shop_name', 'like', "%$name%"];
        if (!empty($region_id_3)) $where[] = ['bs.region_id_3', '=', $region_id_3];//区
        if (!empty($region_id_4)) $where[] = ['bs.region_id_4', '=', $region_id_4];//商圈
        if (!empty($type_id)) $where[] = ['bs.type_ids', 'like', '%,' . $type_id . ',%'];//门店类型

        //创建时间段
        $str = betweenTime($from_time, 'bs.create_time');
        if (!empty($str)) $where[] = $str;

        $list = (new BusinessShop())->alias('bs')
            ->with('region_3')
            ->with('region_4')
            ->join('business_member bm', 'bm.id=bs.business_id', 'left')
            ->where($where)->group('bs.id')
            ->field('bs.id as bs_id, bs.shop_id as id, bs.shop_name, bs.address, bm.nickname, bm.mobile, bs.create_time, bs.region_id_3, bs.region_id_4, bs.type_ids')
            ->paginate($list_rows, false, ['query' => $this->request->get()]);
        foreach ($list as &$v) {
            $v['type_ids'] = explode(',', trim($v['type_ids'], ','));
        }

        $type_list = ShopTypes::getAllTypeToS();
        $region_list_3 = Regions::where('parent_id', $city_id)->column('name', 'id');

        $this->assign('list', $list);
        $this->assign('type_list', $type_list);
        $this->view->assign('region_list_3', $region_list_3);
        $this->view->assign('is_super', $this->auth->check('dashboard/has_look_fields'));

        return $this->view->fetch();
    }

    /**
     * 认领待审核详情
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function pending_shop_do()
    {
        $ids = request()->get('ids');
        if (empty($ids)) {
            $this->error(__('Parameter exception'));
        }
        $row = (new BusinessShop())
            ->where('id', $ids)
            ->where('status', 'in', [0, 2])
            ->find();
        if (empty($row)) {
            $this->error(__('找不到相关门店详情'));
        }
        $business_info = json_decode($row['business_info'], true);
        $shop_info = json_decode($row['shop_info'], true);
        $shop_info['type_ids'] = explode(',', $shop_info['type_ids']);
        $business_hours_new = $shop_info['business_hours_new'];
        if (!empty($business_hours_new)) {
            $business_hours_new = handleBusinessHours($business_hours_new);
            $shop_info['business_hours_new'] = $business_hours_new;
        }

        //附加数据
        $type_list = (new ShopTypes())->where('is_show', 1)->column('name', 'id');//门店类型列表
        $region = Regions::column('name', 'id');

        $this->assign('city_id', $this->city_id);
        $this->assign('business_type', $this->business_type);
        $this->assign('type_list', $type_list);
        $this->assign('region', $region);
        $this->assign('business_info', $business_info);
        $this->assign('shop_info', $shop_info);
        $this->assign('ids', $ids);
        return $this->view->fetch();
    }

    /**
     * 门店认领审核接口
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function check_claim_shop()
    {
        if (\request()->isPost()) {
            $status = request()->post('status');
            $msg = request()->post('msg');
            $ids = request()->post('ids');

            if ($status == null || empty($ids)) {
                $this->error(__('缺失参数'));
            }
            if ($status != 1 && empty($msg)) { // 拒绝认领 需要有理由
                $this->error(__('拒绝认领,需要有理由'));
            }
            $business_m = new BusinessShop();
            $shop_m = new ShopsModel();
            $shop_type_shop_m = new ShopTypeShop();
            $shop_album_m = new ShopAlbum();
            $business_shop_m = new BusinessShop();
            $shop_paperwork_m = new ShopPaperwork();
            $business_message_m = new BusinessMessage();
            $time = date('Y-m-d H:i:s', time());

            $business_shop = $business_m->where('id', $ids)->find();
            if (empty($business_shop)) {
                $this->error(__('找不到门店认领记录'));
            }
            if ($business_shop['status'] != 2) {
                $this->error(__('门店认领状态异常'));
            }

            $shop_info = $business_info = [];
            if ($status == 1) {
                $shop = $shop_m->where('id', $business_shop['shop_id'])->where('is_delete', 0)->where('status', 1)->find();
                if (!empty($shop) && $shop['is_user_status'] == 1) {
                    $this->error(__('找不到门店或门店已被认领'));
                }
                $shop_info = json_decode($business_shop['shop_info'], true);
                if (empty($shop_info) || !is_array($shop_info)) {
                    $this->error(__('认领信息异常！'));
                }
                $shop_info['is_user_status'] = 1;
                $business_info = json_decode($business_shop['business_info'], true);
                if (empty($business_info) || !is_array($business_info)) {
                    $this->error(__('认领信息异常！'));
                }
            }
            $order_business_id = $business_m->where('id', '<>', $ids)->where('shop_id', $business_shop['shop_id'])->column('business_id');

            Db::startTrans();
            try {
                if ($status == 1) { // 批准认领
                    $type_ids = trim($shop_info['type_ids'], ',');
                    $preview_images = trim($shop_info['preview_images'], ',');
                    $type_ids = explode(',', $type_ids);
                    $preview_images = explode(',', $preview_images);
                    unset($shop_info['type_ids'], $shop_info['preview_images']);
                    $shop_id = $business_shop['shop_id'];

                    // 门店添加或修改
                    if (empty($shop)) {
                        $shop_info['data_source'] = 3;
                        $shop_info['create_time'] = date('Y-m-d H:i:s', time());
                        $shop_id = $shop_m->insertGetId($shop_info);
                    } else {
                        $shop_m->where('id', $shop_id)->update($shop_info);
                    }

                    // 删除分类
                    $shop_type_shop_m->where('shop_id', $shop_id)->delete();
                    // 增加分类数据
                    $data = [];
                    foreach ($type_ids as $value) $data[] = ['shop_id' => $shop_id, 'type_id' => $value];
                    $shop_type_shop_m->insertAll($data);

                    // 删除相册
                    $shop_album_m->where('shop_id', $shop_id)->delete();
                    // 加入相册
                    $data = [];
                    foreach ($preview_images as $value) $data[] = ['shop_id' => $shop_id, 'image_url' => $value];
                    $shop_album_m->insertAll($data);

                    // 认领记录状态调整
                    $data = ['status' => 1, 'shop_id' => $shop_id];
                    $business_shop_m->where('id', $ids)->update($data);

                    // 把认证信息移到门店信息中
                    $data = [];
                    foreach ($business_info as $k => $v) {
                        if ($k == 'true_name') $k = 'name';
                        $data[$k] = $v;
                    }
                    $data['shop_id'] = $shop_id;
                    // 加入门店认证资质
                    $shop_paperwork_m->where('shop_id', $shop_id)->delete();
                    $shop_paperwork_m->insert($data);

                    // 把其他认领都拿掉
                    $data = ['message' => '有其他商户已认领该门店！', 'status' => 4];
                    $business_m->where('id', '<>', $ids)->where('shop_id', $shop_id)->update($data);

                    // 极光推送 (成功消息)
                    jPush('门店认领成功啦！', ["{$business_shop['business_id']}"], [], [
                        'sound' => 'sound',
                        'badge' => '+1',
                    ], [], '亲，你的门店认领已审核通过了哦！尽快来打理你的门店吧。', [
                        'title' => '门店认领成功啦',
                        'extras' => ['category' => '1', 'sign_id' => '1', 'id' => '0']
                    ]);

                    $order_business_id = array_map(function ($ab) {
                        return "{$ab}";
                    }, $order_business_id);
                    // 极光推送 (失败消息)
                    jPush('门店认领失败了哦！', $order_business_id, [], [
                        'sound' => 'sound',
                        'badge' => '+1',
                    ], [], '你认领的门店已经被别人认领了哦！请如实认领其他门店。', [
                        'title' => '门店认领失败了哦！',
                        'extras' => ['category' => '1', 'sign_id' => '1', 'id' => '0']
                    ]);

                    // 发送站内信
                    $data = [];
                    $data[0] = [
                        'business_id' => $business_shop['business_id'],
                        'title' => '《' . $business_shop['shop_name'] . '》认领成功！',
                        'content' => '亲，你的门店认领已审核通过了哦！尽快来打理你的门店吧。',
                        'create_time' => $time
                    ];
                    for ($i = 1; $i <= count($order_business_id); $i++) {
                        $data[$i] = [
                            'business_id' => $order_business_id[$i-1],
                            'title' => '《' . $business_shop['shop_name'] . '》认领失败！',
                            'content' => '你认领的门店已经被别人认领了哦！请如实认领其他门店。',
                            'create_time' => $time
                        ];
                    }
                    $business_message_m->insertAll($data);
                } else { // 拒绝认领
                    $data = ['message' => $msg, 'status' => 3];
                    (new BusinessShop())->where('id', $ids)->update($data);
                    // 极光推送 (失败消息)
                    jPush('门店认领失败了哦！', ["{$business_shop['business_id']}"], [], [
                        'sound' => 'sound',
                        'badge' => '+1',
                    ], [], '你认领的门店信息与提交的工商局认证资料不符哦！请重新核对后提交。', [
                        'title' => '门店认领失败了哦！',
                        'extras' => ['category' => '1', 'sign_id' => '1', 'id' => '0']
                    ]);

                    // 发送站内信
                    $data = [
                        'business_id' => $business_shop['business_id'],
                        'title' => '《' . $business_shop['shop_name'] . '》认领失败！',
                        'content' => '你认领的门店信息与提交的工商局认证资料不符哦！请重新核对后提交。',
                        'create_time' => $time
                    ];
                    $business_message_m->insert($data);
                }
                Db::commit();
                $this->success('审核完成');
            } catch (Exception $e) {
                Db::rollback();
                $this->error($e->getMessage());
            }
        }
        $ids = \request()->get('ids');
        if (empty($ids)) $this->error('缺失参数');
        $this->assign('ids', $ids);
        return $this->view->fetch();
    }

    /**
     * 百度api
     * @return string
     * @throws \think\exception\DbException
     */
    public function baidu_map()
    {
        $city = Regions::get($this->city_id);
        if (empty($city)) $this->error('没有找到相应城市');

        $this->view->assign('city', $city);
        return $this->view->fetch();
    }
}
