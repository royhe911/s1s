<?php

namespace app\index\controller;

use think\Controller;

use app\common\model\feigo\Goods;
use app\common\model\feigo\ShopComment;
use app\common\model\feigo\Shops;
use app\common\model\feigo\Article;
use function think\order;

class Share extends Controller
{

    /**
     * 商品分享
     * @Author: zhuhaijun
     * @Date: 2018/4/27
     */
    public function good($id = null)
    {
        $id = intval($id);

        if (empty($id) || $id <= 0) $this->error(__('非法操作！'));

        $result = Goods::with("goodImages")->with("goodDetailImages")
        ->where("id", $id)
        ->where("status", 1)
        ->where("is_delete", 0)
        ->field('id, title, price, preview_image, describe, is_shop')
        ->find();

        if (empty($result['id'])) $this->error(__('您访问的商品不存在！'));
        
        $this->view->assign('data', $result);
        return $this->fetch('good');
    }

    /**
     * 门店分享
     * @Author: zhuhaijun
     * @Date: 2018/4/27
     */
    public function shop($id = null)
    {
        $id = intval($id);

        if (empty($id) || $id <= 0) $this->error(__('非法操作！'));

        $where = [['a.id', '=', $id], ['a.is_delete', '=', 0]];
        $result = Shops::alias('a')
            ->join('good_shop b', 'a.id=b.shop_id', 'inner')
            ->where($where)
            ->field('a.*,GROUP_CONCAT(b.good_id) as good_ids')
            ->find();

        if (empty($result['id'])) $this->error(__('您所访问的门店不存在！'));

        //获取商品列表
        if (!empty($result['good_ids'])) {
            $where = [['a.status', '=', 1], ['a.is_delete', '=', 0]];
            $goods_list = Goods::alias('a')
                ->where($where)
                ->whereIn('a.id', $result['good_ids'])
                ->join('brands b', 'a.brand_id=b.id')
                ->field('a.*,b.name as brand_name')
                ->limit(6)
                ->select();
            $result['good_list'] = empty($goods_list) ? null : $goods_list->toArray();
        } else {
            $result['good_list'] = null;
        }

        //获取评论列表
        $where = [['shop_id', '=', $id], ['is_show', '=', 1], ['is_delete', '=', 0]];
        $comment_list = ShopComment::where($where)->limit(6)->order("create_time desc")->select();
        $result['comment_list'] = $comment_list ? $comment_list->toArray() : null;

        //获取附近的门店
        if (!empty($result['latitude']) && !empty($result['longitude'])) {
            $i = 1; //差值可自定义，值越大，范围就越大
            $min_latitude = $result['latitude'] - $i; //纬度最小值
            $max_latitude = $result['latitude'] + $i; //纬度最大值
            $min_longitude = $result['longitude'] - $i; //经度最小值
            $max_longitude = $result['longitude'] + $i; //经度最大值

            $shop_list = Shops::alias('a')
                ->join('regions b', 'b.id=a.region_id_3', 'left')
                ->where("a.latitude BETWEEN $min_latitude AND $max_latitude")
                ->where("a.longitude BETWEEN $min_longitude AND $max_longitude")
                ->where('a.is_delete', 0)
                ->limit(2)
                ->field("a.*,b.name as area_name,(st_distance (point (a.longitude, a.latitude),point({$result['longitude']},{$result['latitude']}) ) * 111195) as distance")
                ->select();

            $result['shop_list'] = empty($shop_list) ? null : $shop_list->toArray();
        } else {
            $result['shop_list'] = null;
        }

        $this->view->assign('data', $result);
        return $this->fetch();
    }

    /**
     * 新闻资讯分享
     * @Author: zhuhaijun
     * @Date: 2018/4/27
     */
    public function news($id = null)
    {
        $id = intval($id);

        if (empty($id) || $id <= 0) $this->error(__('非法操作！'));

        $where = [['id', '=', $id], ['is_show', '=', 1]];
        $result = Article::where($where)->find();

        if (empty($result['id'])) $this->error(__('您访问的资讯不存在！'));

        $this->view->assign('data', $result);
        return $this->fetch();
    }
}
