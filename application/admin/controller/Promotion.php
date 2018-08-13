<?php
namespace app\admin\controller;

use app\common\controller\Backend;
use app\common\model\feigo\Regions;
use app\common\model\feigo\ShopPromotion;
use app\common\model\feigo\Shops;

class Promotion extends Backend
{
    protected $relationSearch = true;
    protected $layout = 'default2';

    /**
     * 活动列表
     * @return string|\think\response\Json
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $city_id = $this->city_id;

        $promotion_name = request()->get('promotion_name');
        $shop_name = request()->get('shop_name');
        $status = request()->get('status', -1);
        $time = request()->param('start_time');
        $list_rows = $this->cur_limit;

        $w[] = ['s.region_id_2', '=', $city_id];
        if ($promotion_name != null && !empty($promotion_name)) $w[] = ['a.title', 'like', '%' . $promotion_name . '%'];
        if ($shop_name != null && !empty($shop_name)) $w[] = ['s.name', 'like', '%' . $shop_name . '%'];
        if ($status != null && $status >= 0) $w[] = ['a.status', '=', $status];
        $str = betweenTime($time, 'a.create_time');
        if (!empty($str)) $w[] = $str;

        $list = (new ShopPromotion())->alias('a')
            ->join('shops s', 's.id=a.shop_id')
            ->field('a.*, s.name')
            ->where($w)
            ->order('a.create_time', 'desc')
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
            $title = request()->post('title');
            $shop_id = request()->post('shop_id');
            $desc = request()->post('desc');
            $status = request()->post('status', 1);
            $content = request()->post('license_img');
            $time = request()->post('time');

            $time_arr = explode(' - ', $time);
            $start_time = $time_arr[0];
            $end_time = $time_arr[1];

            if (empty($title) || empty($shop_id)|| empty($content) || empty($start_time) || empty($end_time) || empty($desc)) $this->error(__('请完善活动信息！'));

            $shop_info = Shops::where('id', $shop_id)->field('preview_image as image_url,region_id_1,region_id_2,region_id_3,region_id_4')->find();

            if (!$shop_info) $this->error(__('请选择正确的门店！'));

            $data = [
                'title' => $title,
                'shop_id' => $shop_id,
                'desc' => $desc,
                'status' => $status,
                'content' => $content,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'create_time' => date('Y-m-d H:i:s', time()),
            ];

            $data = array_merge($shop_info->toArray(), $data);

            if (ShopPromotion::create($data)) {
                $this->success(__('Operation completed'));
            }
            $this->error(__('Operation failed'));
        }

        $shop_list = Shops::where('status', 1)->where('region_id_2', $this->city_id)->column('name,region_id_3,region_id_4', 'id');
        $this->view->assign('region_data', Regions::getTreeN($this->city_id));
        $this->view->assign('shop_list', $shop_list);
        return $this->view->fetch('edit');
    }

    /**
     * 编辑
     * @param null $ids
     * @return string
     */
    public function edit($ids = null)
    {
        $row = ShopPromotion::get($ids);
        if (!$row) $this->error(__('No Results were found'));

        if ($row['status'] == 1) $this->error(__('启用状态的数据不可编辑！'));

        if (request()->isAjax()) {
            $title = request()->post('title');
            $shop_id = request()->post('shop_id');
            $desc = request()->post('desc');
            $status = request()->post('status', 1);
            $content = request()->post('license_img');
            $time = request()->post('time');

            $time_arr = explode(' - ', $time);
            $start_time = $time_arr[0];
            $end_time = $time_arr[1];

            if (empty($title) || empty($shop_id)|| empty($content) || empty($start_time) || empty($end_time) || empty($desc)) $this->error(__('请完善活动信息！'));

            $shop_info = Shops::where('id', $shop_id)->field('preview_image as image_url,region_id_1,region_id_2,region_id_3,region_id_4')->find();

            if (!$shop_info) $this->error(__('请选择正确的门店！'));

            $data = [
                'title' => $title,
                'shop_id' => $shop_id,
                'desc' => $desc,
                'status' => $status,
                'content' => $content,
                'start_time' => $start_time,
                'end_time' => $end_time
            ];
            $data = array_merge($shop_info->toArray(), $data);
            $bool = ShopPromotion::where('id', $ids)->update($data);
            if ($bool === false) throw new Exception('服务器忙，请稍后··');

            $this->success(__('Operation completed'));
        }

        $shop_list = Shops::where('status', 1)->where('region_id_2', $this->city_id)->column('name,region_id_3,region_id_4', 'id');
        $this->view->assign('region_data', Regions::getTreeN($this->city_id));
        $this->view->assign('shop_list', $shop_list);
        $this->view->assign('row', $row);
        return $this->view->fetch('edit');
    }

    /**
     * 编辑状态
     */
    public function edit_status()
    {
        $ids = request()->param('ids');
        $status = request()->param('status');
        if (empty($ids) || $status == null) $this->error(__('Parameter exception'));

        $bool = ShopPromotion::where('id', 'in', $ids)->where('status', $status)->field('id')->find();
        if ($bool) $this->error(__('已有' . ($status == 1 ? '启用状态' :'禁用状态') . '的数据！请重新选择！'));

        if ($status == 0){
            $bool = ShopPromotion::where('id', 'in', $ids)->where('is_index', 1)->field('id')->find();
            if ($bool) $this->error(__('有正在推荐中的数据！请重新选择！'));
        }

        $bool = ShopPromotion::where('id', 'in', $ids)->setField('status', $status);
        if (!$bool) $this->error(__('Operation failed'));
        $this->success(__('Operation completed'));
    }

    /**
     * 推荐首页
     */
    public function edit_index()
    {
        $ids = request()->param('ids');
        $status = request()->param('status');

        if (empty($ids) || $status == null)
            $this->error(__('Parameter exception'));

        if ($status == 1) {
            if (ShopPromotion::where('id', 'in', $ids)->where('status', 0)->find()) {
                $this->error(__('有禁用状态的活动，不能推荐'));
            }
        }

        $bool = ShopPromotion::where('id', 'in', $ids)->where('is_index', $status)->field('id')->find();

        if ($bool) $this->error(__('已有' . ($status == 1 ? '推荐状态' :'关闭推荐状态') . '的数据！请重新选择！'));

        if (ShopPromotion::where('id', 'in', $ids)->setField('is_index', $status)) {
            $this->success(__('Operation completed'));
        }
        $this->error(__('Operation failed'));
    }

    /**
     * 删除
     */
    public function delete()
    {
        $ids = request()->param('ids');
        if (empty($ids))
            $this->error(__('Parameter exception'));

        if (ShopPromotion::where('id', 'in', $ids)->where('status', 1)->find()) {
            $this->error(__('有活动在启用状态，不能删除'));
        }

        if (ShopPromotion::where('id', 'in', $ids)->delete()) {
            $this->success(__('Operation completed'));
        }
        $this->error(__('Operation failed'));
    }
}
