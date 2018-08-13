<?php
namespace app\common\model\feigo;

use think\Model;

class GoodTypes extends Model
{
    protected $connection = [
        'database' => 'feigo',
    ];

    protected $table = 'feigo_good_types';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';

    /**
     * 获取所有分类
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getAllType()
    {
        $list = cache("good_types_all");

        if (empty($list)) {
            $list = self::order("sort")->select()->toArray();
            cache("good_types_all", $list);
        }
        return $list;
    }

    /**
     * 获取分类数据总数
     * @return int
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getDataTotal()
    {
        return count(self::getAllType());
    }

    /**
     * 获取层级
     * @param bool $refresh
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getTreeAll($refresh = false)
    {
        $list = cache('good_types_all_tree');

        if ($refresh || empty($list)) {
            $list = [];
            $all = self::getAllType();

            if (!empty($all)) {
                foreach ($all as &$value) $list[$value['pid']][$value['id']] = $value;

                cache('good_types_all_tree', $list);
            }
        }
        return $list;
    }

    /**
     * 返回n级html<select></select>中option包含选中
     * @param int $pid
     * @param int $select_id
     * @param bool $is_show_all
     * @param bool $re_type
     * @param int $level
     * @param null $tree_all
     * @return null|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getTypeListHtml($pid = 0, $select_id = 0, $is_show_all = false, $re_type = false, $level = 0, &$tree_all = null)
    {
        $html = '';
        if (empty($tree_all)) $tree_all = self::getTreeAll(false);
        if (empty($tree_all[$pid])) return null;
        foreach ($tree_all[$pid] as $value) {
            $text = str_repeat('&nbsp;', $level * 4) . $value['name'];
            $html .= sprintf('<option %s value="%s">%s</option>', $select_id == $value['id'] ? 'selected': '', $value['id'], $text);
            if (isset($tree_all[$value['id']])) $html .= self::getTypeListHtml($value['id'], $select_id, $is_show_all, $re_type, $level + 1, $tree_all);
        }
        return $html;
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
        $tree_list = cache('good_types_tree');
        if(empty($tree_list)){
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
            cache('good_types_tree', $tree);
            return $tree;
        }
        return $tree_list;
    }
    /**
     * 获取分类的全部父类
     * @return array
     */
    public static function getTypeParent($type_id){
        if(empty($type_id)){
            return '';
        }
        $str = '';
        $list = self::where('id',$type_id)->find()->toArray();
        if($list['pid'] == '0'){
            return (string)$list['id'];
        }else{
            $str.= self::getTypeParent($list['pid']).',';
            $str.= $list['id'];
        }
        return $str;

    }


}
