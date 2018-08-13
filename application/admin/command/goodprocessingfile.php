<?php
namespace app\admin\command;

use app\common\model\feigo\GoodImport;
use app\common\model\feigo\ShopImport;
use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Argument;
use think\Db;
use think\Exception;
use think\facade\Log;

class goodprocessingfile extends Command
{

    protected function configure()
    {
        $this->setName('goodprocessingfile')
            ->addArgument('name', Argument::OPTIONAL, "your name")
            ->setDescription('Here is the remark ');
    }


    /**
     * 商品的下载  解压  上传
     * @param Input $input
     * @param Output $output
     */
    protected function execute(Input $input, Output $output)
    {
        Db::startTrans();
        try {
            $import = GoodImport::where('status', 0)->where('count', '<', 3)->lock(true)->order('id', 'asc')->order('count', 'asc')->find();
            if (empty($import)) {
                Log::info('没有要处理的商品数据');
                Db::rollback();die;
            }
            $import = $import->toArray();
            $branch_no = $import['branch_no'];
            $qiniu = new \Qiniu();
            $file_keys = [];
            $xls_file = '商品信息.xls';

            // 下载路径
            $download_url = $qiniu->privateDownloadUrl(config("qiniu.private_domain") . $import['url']);
            $file_name = $branch_no;
            $file_ext = pathinfo($import['url'], PATHINFO_EXTENSION);
            $file_path = ROOT_PATH . 'public/upload/';
            file_put_contents($file_path . $file_name . "." . $file_ext, file_get_contents($download_url));

            $archive = new \PclZip($file_path . $file_name . "." . $file_ext);
            if ($archive->extract(PCLZIP_OPT_PATH, $file_path . $file_name) == 0) {
                throw new Exception("Error : " . $archive->errorInfo(true), 100);
            }

            unlink($file_path . $file_name . "." . $file_ext);
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
                if (strpos($value, '._') !== false || strpos($value, '.DS_Store') !== false) {
                    continue;
                }
                $result = ShopImport::uploadFile($value, config('qiniu.img_prefix') . $file_name, $token, 'good');
                $one_name = basename(dirname(dirname(dirname($result['key']))));
                $two_name = basename(dirname(dirname($result['key'])));
                $thr_name = basename(dirname($result['key']));
                $key = $one_name . '/' . $two_name . '/' . $thr_name;
                $file_keys[$key][] = $result['key'];
            }
            $data = [
                'file_keys' => $file_keys,
                'xls_file' => $xls_file,
                'shop_id' => $import['shop_id'],
                'member_id' => $import['member_id'],
            ];
            cache($branch_no, $data);
            GoodImport::where('branch_no', $branch_no)->update(['status' => 1, 'count' => 0]);
            Db::commit();
        } catch (Exception $e) {
            Log::info($e->getMessage());
            Log::info($import);
            GoodImport::where('branch_no', $branch_no)->setInc('count');
            Db::commit();
            $output->error($e->getMessage() . '----good---' . $import['id']);die;
        }
        $output->info('SUCCESS----good---' . $import['id']);
    }
}