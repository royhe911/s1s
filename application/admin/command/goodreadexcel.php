<?php
namespace app\admin\command;

use app\common\model\feigo\GoodImport;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\Db;
use think\Exception;
use think\facade\Log;

class goodreadexcel extends Command
{

    protected function configure()
    {
        $this->setName('goodreadexcel')
            ->addArgument('name', Argument::OPTIONAL, "your name")
            ->setDescription('Here is the remark ');
    }


    /**
     * 商品的读取excel文件
     * @param Input $input
     * @param Output $output
     */
    protected function execute(Input $input, Output $output)
    {
        Db::startTrans();
        try {
            $import = GoodImport::where('status', 1)->order('id', 'asc')->find();
            if (empty($import)) {
                Log::info('没有要处理的商品数据');
                Db::rollback();die;
            }
            $import = $import->toArray();
            $branch_no = $import['branch_no'];
            $data = cache($branch_no);
            $file_name = ROOT_PATH . 'public/upload/' . $branch_no . '/' . $data['xls_file'];
            if (!is_file($file_name)) {
                throw new Exception("Error : 找不到excel文件----good---" . $import['id'], 100);
            }
            $info = file_get_contents($file_name);
            if ($info) {
                $header = [
                    'A' => 'title',
                    'B' => 'price',
                    'C' => 'images_arr',
                    'D' => 'detail_image',
                    'E' => 'brand',
                    'F' => 'type_id',
                ];
                $perLimit = 100;
                $count = \Excel::parse($file_name, $header, $perLimit, $data, $branch_no, function ($data) {
                    (new \RedisM)->rpush('import_good_list_queue', json_encode($data));
                }, '');
            }
            cache($branch_no, null);
            GoodImport::where('branch_no', $branch_no)->setField('status', 2);
            Log::write("导入成功" . $count . "条数据");
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::info($import);
            ShopImport::where('branch_no', $branch_no)->setInc('count');
            Db::commit();
            $output->error($e->getMessage() . '----good---' . $import['id']);die;
        }
        $output->info("导入成功" . $count . "条数据");
    }
}