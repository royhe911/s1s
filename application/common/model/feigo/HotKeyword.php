<?php
namespace app\common\model\feigo;

use think\Model;

class HotKeyword extends Model
{
    protected $connection = [
        'database' => 'feigo',
    ];

    protected $table = 'feigo_hot_keyword';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';

    /**
     * 获取热搜关键词列表
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getList()
    {
        $list = cache("hot_keyword_all");
        if (empty($list)) {
            $list = self::order("sort asc")->select()->toArray();
            cache("hot_keyword_all", $list);
        }
        return $list;
    }
}
