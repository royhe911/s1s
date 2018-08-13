<?php
namespace app\common\model\feigo;

use think\facade\Log;
use think\Model;

class ShopImport extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];

    protected $table = 'feigo_shop_import';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';

    /**
     * 获取新的版本号
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getNewBranch()
    {
        $branch_no = date("Ymd") . rand(1, 99999);
        $import_tmp = self::order("branch_no desc")->field("branch_no")
            ->where("branch_no", $branch_no)
            ->find();

        if (! empty($import_tmp)) {
            $branch_no = self::getNewBranch();
        }
        return $branch_no;
    }

    /**
     * 上传图片 带上目录结构
     * @param $value
     * @param $file_name
     * @param $token
     * @return bool
     */
    public static function uploadFile($value, $file_name, $token, $type = 'shop')
    {
        if ($type == 'shop') {
            $result = \Qiniu::instance()->uploadOne($value, "", $file_name . '/' . basename(dirname(dirname($value))) . "/" . basename(dirname($value)) . '/' . pathinfo($value, PATHINFO_FILENAME) . "." . pathinfo($value, PATHINFO_EXTENSION), $token);
        } else {
            $result = \Qiniu::instance()->uploadOne($value, "", $file_name . '/' . basename(dirname(dirname(dirname($value)))) . "/" . basename(dirname(dirname($value))) . '/' . basename(dirname($value)) . '/' . pathinfo($value, PATHINFO_FILENAME) . "." . pathinfo($value, PATHINFO_EXTENSION), $token);
        }
        if (empty($result) || empty($result['key'])) {
            Log::info("upload_error:" . $value);
            self::uploadFile($value, $file_name, $token, $type);
        }
        return $result;
    }
}
