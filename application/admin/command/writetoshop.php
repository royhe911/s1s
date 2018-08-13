<?php
namespace app\admin\command;

use app\common\model\feigo\GoodDetailImages;
use app\common\model\feigo\GoodImages;
use app\common\model\feigo\Goods;
use app\common\model\feigo\GoodShop;
use app\common\model\feigo\ShopbyGoodTypes;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\facade\Log;

class writetoshop extends Command
{

    protected function configure()
    {
        $this->setName('writetoshop')->setDescription('Here is the remark ');
    }


    /**
     * 城市数据导入
     * @param Input $input
     * @param Output $output
     */
    protected function execute(Input $input, Output $output)
    {
        $list = (new \RedisM())->lrange('citydata_queue');
        if (!empty($list)) {
            foreach ($list as $list2) {
                (new \RedisM())->lrem('citydata_queue', $list2);
                $list2 = json_decode($list2, true);
                $shop_id = $list2['shop_id'];
                $good_id = $list2['id'];
                $list2['is_shop'] = 1;
                // 描述详情
                $desc_url = GoodDetailImages::where('good_id', $good_id)->select()->toArray();
                if (empty($desc_url)) {
                    Goods::where('id', $good_id)->setField('status', 0);
                }
                // 商品图片
                $image_url = GoodImages::where('good_id', $good_id)->select()->toArray();
                if (empty($image_url)) {
                    Goods::where('id', $good_id)->setField('status', 0);
                }
                // 商品分类
                $w = [
                    'shop_id' => $shop_id,
                    'pid' => $list2['pid'],
                    'name' => $list2['type_name'],
                ];
                $type = ShopbyGoodTypes::where($w)->find();

                unset($list2['shop_id'], $list2['id'], $list2['pid'], $list2['type_name']);
                Db::startTrans();
                try {
                    if (empty($type)) {
                        $type_id = (new ShopbyGoodTypes)->insertGetId($w);
                    } else {
                        $type_id = $type['id'];
                    }
                    $list2['good_type_id'] = $type_id;
                    $good_id = (new Goods())->insertGetId($list2);
                    foreach ($desc_url as $k => $v) {
                        unset($desc_url[$k]['sort']);
                        $desc_url[$k]['good_id'] = $good_id;
                    }
                    (new GoodDetailImages)->insertAll($desc_url);

                    $data = [
                        'good_id' => $good_id,
                        'shop_id' => $shop_id,
                    ];
                    (new GoodShop)->insert($data);

                    foreach ($image_url as $k => $v) {
                        unset($image_url[$k]['id']);
                        $data[$k]['good_id'] = $good_id;
                    }
                    (new GoodImages())->insertAll($data);

                    Db::commit();
                    Log::write('商品导入门店成功');
                } catch (Exception $e) {
                    Db::rollback();
                    Log::write('商品导入门店成功');
                }
            }
        }
        dump('SUCCESS');
    }
}