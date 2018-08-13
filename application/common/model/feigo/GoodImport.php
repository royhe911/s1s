<?php
namespace app\common\model\feigo;

use think\Model;

class GoodImport extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];

    protected $table = 'feigo_good_import';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';

    /**
     * 获取新的批次号
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getNewBranch()
    {
        $branch_no = date("Ymd") . rand(1, 999999);
        $import_tmp = self::order("branch_no desc")->field("branch_no")
            ->where("branch_no", $branch_no)
            ->find();

        if (! empty($import_tmp)) {
            $branch_no = self::getNewBranch();
        }
        return $branch_no;
    }
}
