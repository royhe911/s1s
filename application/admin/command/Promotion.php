<?php
namespace app\admin\command;

use app\common\model\feigo\Promotion as PromotionM;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;

class Promotion extends Command
{

    protected function configure()
    {
        $this->setName('Promotion')
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
        $time = date('Y-m-d H:i:s',time());
        $promotion_list = PromotionM::where('status', 'in','0,1')->select()->toArray();

        $start_ids = [];
        $end_ids = [];
        foreach ($promotion_list as $v) {
            if($v['status']=='0' && $v['start_time'] >= $time){
                $start_ids[] = $v['id'];
            }
            if($v['status']=='1' && $v['end_time'] < $time){
                $end_ids[] = $v['id'];
            }
        }
        if($start_ids){
            PromotionM::where('id', 'in',$start_ids)->update(['status'=>'1']);
        }
        if($end_ids){
            PromotionM::where('id', 'in',$end_ids)->update(['status'=>'2','cutout_time'=>$time]);
        }
        dump('SUCCESS');
    }
}