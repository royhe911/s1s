<?php
namespace app\common\model\feigo;

use think\Model;

class ArticleType extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];

    protected $table = 'feigo_article_type';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';

    /**
     * 获取所有资讯分类
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getAllType()
    {
        $list = cache("article_type_all");
        if (empty($list)) {
            $list = self::order("sort asc")->field('id, name, sort')->select()->toArray();
            cache("article_type_all", $list);
        }
        return $list;
    }
}
