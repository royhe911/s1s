<?php
namespace app\admin\command;

use app\common\model\feigo\ShopImport;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\Db;
use think\Exception;
use think\facade\Log;

class shopreadexcel extends Command
{

    protected function configure()
    {
        $this->setName('shopreadexcel')
            ->addArgument('name', Argument::OPTIONAL, "your name")
            ->setDescription('Here is the remark ');
    }


    /**
     * 门店的读取excel文件
     * @param Input $input
     * @param Output $output
     */
    protected function execute(Input $input, Output $output)
    {
        Db::startTrans();
        try {
            $import = ShopImport::where('status', 1)->where('count', '<', 3)->lock(true)->order('id', 'asc')->order('count', 'asc')->find();
            if (empty($import)) {
                Log::info('没有要处理的店铺数据');
                Db::rollback();die;
            }
            $import = $import->toArray();
            $branch_no = $import['branch_no'];
            $data = cache($branch_no);
            $file_name = ROOT_PATH . 'public/upload/' . $branch_no . '/' . $data['xls_file'];
            if (!is_file($file_name)) {
                throw new Exception("Error : 找不到excel文件----shop---" . $import['id'], 100);
            }
            $info = file_get_contents($file_name);
            if ($info) {
                $header = [
                    'A' => 'name',
                    'B' => 'images_arr',
                    'C' => 'region_id_1',
                    'D' => 'region_id_2',
                    'E' => 'region_id_3',
                    'F' => 'address',
                    'G' => 'banksite',
                    'H' => 'bankcard',
                    'I' => 'contacts',
                    'J' => 'phone_number',
                    'K' => 'phone_number1',
                    'L' => 'business_hours',
                    'M' => 'brand',
                    'N' => 'type_id',
                    'O' => 'legal_person',
                    'P' => 'acreage',
                    'Q' => 'scale',
                ];
                $perLimit = 100;
                $count = \Excel::parse($file_name, $header, $perLimit, $data, $branch_no, function ($data) use ($branch_no) {
                    (new \RedisM)->rpush('import_shop_list_queue_' . $branch_no, json_encode($data));
                }, '');
                ShopImport::where('branch_no', $branch_no)->setInc('import_count', $count);
            }
            cache($branch_no, null);
            ShopImport::where('branch_no', $branch_no)->setField('status', 2);
            Db::commit();
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::info($import);
            ShopImport::where('branch_no', $branch_no)->setInc('count');
            Db::commit();
            $output->error($e->getMessage() . '----shop---' . $import['id']);die;
        }
        $output->info("导入成功" . $count . "条数据");
    }
}