<?php
namespace app\admin\command;

use app\common\model\feigo\Brands;
use app\common\model\feigo\Goods;
use app\common\model\feigo\Shops;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;

class citydata extends Command
{

    protected function configure()
    {
        $this->setName('citydata')
            ->addArgument('name', Argument::OPTIONAL, "your name")
            ->setDescription('Here is the remark ');
    }


    /**
     * 城市的数据整理
     * @param Input $input
     * @param Output $output
     */
    protected function execute(Input $input, Output $output)
    {
        $city_id = trim($input->getArgument('name'));
        $city_id = $city_id ?: 431200;

        $shop_list = Shops::where('status', 1)
            ->where('region_id_2', $city_id)
            ->where('data_source', 1)
            ->column('id');
        foreach ($shop_list as $v) {
            $brand = (new Brands())->alias('b')
                ->join('brand_shop bs', 'bs.brand_id=b.id')
                ->where('b.status', 1)
                ->where('bs.shop_id', $v)
                ->column('b.id');
            if (empty($brand)) {
                Shops::where('id', $v)->setField('status', 0);
                continue;
            }
            $good_list = (new Goods)->alias('g')
                ->join('good_detail_images gd', 'gd.good_id=g.id')
                ->join('good_images gi', 'gi.good_id=g.id')
                ->join('good_types gt', 'gt.id=g.good_type_id')
                ->where('g.brand_id', 'in', $brand)
                ->where('g.status', 1)
                ->where('g.is_shop', 0)
                ->field('g.*, gt.name as type_name, gt.pid')
                ->group('g.id')
                ->limit(20)->select()->toArray();
            foreach ($good_list as $v2) {
                $v2['shop_id'] = $v;
                (new \RedisM())->rpush('citydata_queue', json_encode($v2));
            }
        }
        dump('SUCCESS');
    }
}