<?php
namespace app\admin\command;

use app\common\model\feigo\Brands;
use app\common\model\feigo\GoodDetailImages;
use app\common\model\feigo\GoodImages;
use app\common\model\feigo\Goods;
use app\common\model\feigo\GoodShop;
use app\common\model\feigo\ShopbyGoodTypes;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\Db;
use think\Exception;
use think\facade\Log;

class goodimportdata extends Command
{

    protected function configure()
    {
        $this->setName('goodimportdata')
            ->addArgument('name', Argument::OPTIONAL, "your name")
            ->setDescription('Here is the remark ');
    }


    /**
     * 导入商品数据
     * @param Input $input
     * @param Output $output
     */
    protected function execute(Input $input, Output $output)
    {
        $redis = new \RedisM;
        $good_list = $redis->lpop('import_good_list_queue');

        $brand_list = Brands::where('status', 1)->column('name', 'id');
        if (!empty($good_list) || $good_list == false) {
            Log::info('没有要处理的商品数据');
            $output->error('没有要处理的商品数据');die;
        }
        $good = json_decode($good_list, true);
        foreach ($good as $k => $v) {
            $v['err_time'] = date('Y-m-d H:i:s', time());

            // 品牌
            $brand_id = array_search($v['brand'], $brand_list);
            if ($brand_id == false) {
                $v['err'] = '【' . $v['title'] . '】的品牌没有匹配到';
                putLog('import_good_info', $v);
                continue;
            }
            $v['brand_id'] = $brand_id;

            // 分类
            $type_list = ShopbyGoodTypes::where('shop_id', $v['shop_id'])->column('name', 'id');
            if (empty($type_list)) {
                $type_id = -999;
            } else {
                $type_id = array_search($v['type_id'], $type_list);
                if ($type_id == false) $type_id = -999;
            }
            $v['type_id_new'] = $type_id;

            // 图片
            if (empty($v['images']) && !is_array($v['images'])) {
                $v['err'] = '【' . $v['title'] . '】的相册图片没有';
                putLog('import_good_info', $v);
                continue;
            }
            // 详情图片
            if (empty($v['detail_image']) && !is_array($v['detail_image'])) {
                $v['err'] = '【' . $v['title'] . '】的详情图片没有';
                putLog('import_good_info', $v);
                continue;
            }

            $time = date('Y-m-d H:i:s', time());
            $res_data = [
                'title' => $v['title'],
                'brand_id' => $brand_id,
                'price' => $v['price'],
                'preview_image' => $v['images'][0],
                'is_shop' => 1,
                'create_time' => $time,
            ];
            Db::startTrans();
            try {
                // 分类
                if ($type_id == -999) {
                    $type_id = (new ShopbyGoodTypes)->insertGetId(['name' => $v['type_id'], 'shop_id' => $v['shop_id']]);
                }
                $res_data['good_type_id'] = $type_id;
                // 商品
                $good_id = (new Goods())->insertGetId($res_data);
                // 图片
                $res_data = [];
                $i = 1;
                foreach ($v['images'] as $v2) {
                    $res_data[$i]['goods_image'] = $v2;
                    $res_data[$i]['good_id'] = $good_id;
                    $res_data[$i]['create_time'] = $time;
                    $i++;
                }
                (new GoodImages())->insertAll($res_data);

                // 详情图片
                $res_data = [];
                $i = 1;
                foreach ($v['detail_image'] as $v3) {
                    $res_data[$i]['detail_image'] = $v3;
                    $res_data[$i]['good_id'] = $good_id;
                    $res_data[$i]['create_time'] = $time;
                    $i++;
                }
                (new GoodDetailImages())->insertAll($res_data);

                // 绑定店铺
                (new GoodShop())->insert(['good_id' => $good_id, 'shop_id' => $v['shop_id']]);
                Db::commit();
                Log::write('【' . $v['title'] . '】导入成功');
                $output->info('【' . $v['title'] . '】导入成功');
            } catch (Exception $e) {
                Db::rollback();
                $v['err'] = '【' . $v['title'] . '】导入失败，请重新导入。';
                $output->error('【' . $v['title'] . '】导入失败，请重新导入。');
                putLog('import_good_info', $v);
            }
        }
    }
}