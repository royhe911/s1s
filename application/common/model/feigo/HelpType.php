<?php
namespace app\common\model\feigo;

use think\Model;

class HelpType extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];

    protected $table = 'feigo_help_type';

    /**
     * 获取所有帮助分类
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getAllType()
    {
        $list = cache("help_type_all");
        if (empty($list)) {
            $list = self::order("sort asc")->field('id, name, sort')->select()->toArray();
            cache("help_type_all", $list);
        }
        return $list;
    }
}
