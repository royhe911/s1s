<?php
namespace app\common\model\feigo;

use think\Model;

class ShopbyGoodTypes extends Model
{
    protected $connection = [
        'database' => 'feigo',
    ];

    protected $table = 'feigo_shopby_good_types';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';

    /**
     * 获取所有分类
     * @param int $shop_id
     * @return array|mixed|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getAllType($shop_id = 0)
    {
        $all_type_data = cache("shopby_good_types_all_" . $shop_id);
        if (empty($all_type_data)) {
            $all_type_data = self::field("*,COUNT(id) AS has_children ")
                ->order("pid asc,sort asc")
                ->where('shop_id', $shop_id)
                ->group("id")
                ->select()->toArray();
            cache("shopby_good_types_all_" . $shop_id, $all_type_data);
        }
        return $all_type_data;
    }

    /**
     * 分类 多级
     * @param $list
     * @param int $pid
     * @param int $level
     * @return array
     */
    public static function getTree($list, $pid = 0, $level = 0)
    {
        $tree = [];
        if (!empty($list)) { // 先修改为以id为下标的列表
            $newList = [];
            foreach ($list as $k => $v) {
                $v['level'] = 0;
                $newList[$v['id']] = $v;
            } // 然后开始组装成特殊格式
            foreach ($newList as $value) {
                if ($pid == $value['pid']) { // 先取出顶级
                    $tree[] = &$newList[$value['id']];
                } elseif (isset($newList[$value['pid']])) { // 再判定非顶级的pid是否存在，如果存在，则再pid所在的数组下面加入一个字段items，来将本身存进去
                    $newList[$value['id']]['level'] = $newList[$value['pid']]['level'] + 1;
                    if ($level > 0 && $newList[$value['id']]['level'] >= $level) {
                        unset($newList[$value['id']]);
                    } else {
                        $newList[$value['pid']]['type_info'][] = &$newList[$value['id']];
                    }
                }
            }
        }
        return $tree;
    }

    /**
     * 返回n级html<select></select>中option包含选中
     * @param int $pid
     * @param int $select_id
     * @param bool $is_show_all
     * @param bool $re_type
     * @param int $level
     * @param null $tree_all
     * @return string
     * @Author: zhuhaijun
     * @Date: 2018/4/10
     */
    public static function getTypeListHtml($pid = 0, $shop_id = 0, $select_id = 0, $is_show_all = false, $re_type = false, $level = 0, &$tree_all = null)
    {
        $html = '';
        if (empty($tree_all)) $tree_all = self::getTreeAll(false, $shop_id);
        if (empty($tree_all[$pid])) return null;
        foreach ($tree_all[$pid] as $value) {
            $text = str_repeat('&nbsp;', $level * 4) . $value['name'];
            $html .= sprintf('<option %s value="%s">%s</option>', $select_id == $value['id'] ? 'selected': '', $value['id'], $text);
            if (isset($tree_all[$value['id']])) $html .= self::getTypeListHtml($value['id'], $shop_id, $select_id, $is_show_all, $re_type, $level + 1, $tree_all);
        }
        return $html;
    }

    /**
     * 返回二级列表
     * @param bool $refresh 是否刷新
     * @return array|mixed
     * @Author: zhuhaijun
     * @Date: 2018/4/10
     */
    public static function getTreeAll($refresh = false, $shop_id = 0)
    {
        $tree = cache('shopby_good_types_tree_all' . $shop_id);
        if ($refresh || empty($tree)) {
            $tree = [];
            $dataAll = self::getAllType($shop_id);

            if (!empty($dataAll)) {
                foreach ($dataAll as &$value) $tree[$value['pid']][$value['id']] = $value;
                cache('shopby_good_types_tree_all' . $shop_id, $tree);
            }
        }
        return $tree;
    }
}
