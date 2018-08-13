<?php
namespace app\admin\command;

use think\console\Input;
use think\console\Output;
use think\console\Command;
use app\common\model\feigo\Members;

class UserStatsEmail extends Command
{

    protected function configure()
    {
        $this->setName('UserStatsEmail')->setDescription('Here is the remark ');
    }

    /**
     * 统计用户数量发送邮件
     * 
     * @param Input $input            
     * @param Output $output            
     */
    protected function execute(Input $input, Output $output)
    {
        $members = Members::where("create_time", ">", date("Y-m-d 00:00:00"))->where("create_time", "<", date("Y-m-d 23:59:59"))->select();
        $member_count = Members::count();
        $members = $members->toArray();
        if (! empty($members)) {
            $inc_member_count = count($members);
            $mes = '<table><thead><tr><th>手机号　</th><th>　注册时间</th></tr></thead>';
            foreach ($members as $val) {
                $mes .= "<tr><td>手机号：{$val['mobile']}　　</td><td>注册时间：{$val['create_time']}</td></tr>";
            }
            $mes .= '</table>';
            $mes .= "<span>总用户增量:{$inc_member_count}</span> <span>总用户量:{$member_count}";
            $flag = \Mail::sendEmail("zhouhongwei@wnw3d.com", '千寻狗用户统计', $mes);
            // var_dump($flag);
            $flag = \Mail::sendEmail("63463386@qq.com", '千寻狗用户统计', $mes);
            // var_dump($flag);
        }
    }
}