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

class shopprocessingfile extends Command
{

    protected function configure()
    {
        $this->setName('shopprocessingfile')
            ->addArgument('name', Argument::OPTIONAL, "your name")
            ->setDescription('Here is the remark ');
    }


    /**
     * 门店的下载  解压  上传
     * @param Input $input
     * @param Output $output
     */
    protected function execute(Input $input, Output $output)
    {
        Db::startTrans();
        try {
            $import = ShopImport::where('status', 0)->where('count', '<', 3)->lock(true)->order('id', 'asc')->order('count', 'asc')->find();
            if (empty($import)) {
                Log::info('没有要处理的店铺数据');
                Db::rollback();die;
            }
            $import = $import->toArray();
            $branch_no = $import['branch_no'];
            $qiniu = new \Qiniu();
            $file_keys = [];
            $xls_file = '店铺信息.xls';

            // 下载路径
            $file_path = ROOT_PATH . 'public/upload/';
            $file_name = $branch_no;
            $file_ext = pathinfo($import['url'], PATHINFO_EXTENSION);
            if (!is_file($file_path . $file_name . "." . $file_ext)) {
                $download_url = $qiniu->privateDownloadUrl(config("qiniu.private_domain") . $import['url']);
                file_put_contents($file_path . $file_name . "." . $file_ext, file_get_contents($download_url));
            }

            $archive = new \PclZip($file_path . $file_name . "." . $file_ext);
            if ($archive->extract(PCLZIP_OPT_PATH, $file_path . $file_name) == 0) {
                throw new Exception("Error : " . $archive->errorInfo(true), 100);
            }

            //unlink($file_path . $file_name . "." . $file_ext);
            $filenames = getFilenamesByDir($file_path . $file_name);
            Log::info($filenames);
            $token = $qiniu->token("3600", config("qiniu.bucket"));
            foreach ($filenames as $value) {
                if (strpos($value, '.xls') !== false || strpos($value, '.xlsx') !== false) {
                    $xls_file = pathinfo($value, PATHINFO_FILENAME) . "." . pathinfo($value, PATHINFO_EXTENSION);
                }
                if (strpos($value, '.jpg') === false && strpos($value, '.png') === false && strpos($value, '.image') === false) {
                    continue;
                }
                if (strpos($value, '._') !== false) {
                    continue;
                }
                $result = ShopImport::uploadFile($value, config('qiniu.img_prefix') . $file_name, $token);
                $one_name = basename(dirname(dirname($result['key'])));
                $two_name = basename(dirname($result['key']));
                $key = $one_name . '/' . $two_name;
                $file_keys[$key][] = $result['key'];
            }
            $data = [
                'file_keys' => $file_keys,
                'xls_file' => $xls_file,
                'region_id_2' => $import['region_id_2'],
                'member_id' => $import['member_id'],
            ];
            cache($branch_no, $data);
            ShopImport::where('branch_no', $branch_no)->update(['status' => 1, 'count' => 0]);
            Db::commit();
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::info($import);
            ShopImport::where('branch_no', $branch_no)->setInc('count');
            Db::commit();
            $output->error($e->getMessage() . '----shop---' . $import['id']);die;
        }
        $output->info('SUCCESS----shop---' . $import['id']);
    }
}