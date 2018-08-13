<?php
namespace app\common\model\feigo;

use think\Model;

class IndexGroup extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];

    protected $table = 'feigo_index_group';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';

    /**
     * 首页分组下的门店分类
     * @return $this
     */
    public function indexGroupShopType()
    {
        return $this->hasMany('index_group_shop_type', 'index_group_id', 'group_id');
    }

    /**
     * 获取分组和分类
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getGroupAndType()
    {
        $group_list = self::with('indexGroupShopType')
            ->where('is_show', 1)
            ->field('id as group_id, name, image_url')
            ->order('sort')
            ->select()->toArray();
        return $group_list;
    }
}
