<?php

namespace app\admin\model;

use think\Model;

class Admin extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    public function adminCity()
    {
        return $this->hasMany('admin_region', 'admin_id', 'id');
    }


    /**
     * 重置用户密码
     * @author baiyouwen
     */
    public function resetPassword($uid, $NewPassword)
    {
        $passwd = $this->encryptPassword($NewPassword);
        $ret = $this->where(['id' => $uid])->update(['password' => $passwd]);
        return $ret;
    }

    // 密码加密
    protected function encryptPassword($password, $salt = '', $encrypt = 'md5')
    {
        return $encrypt($password . $salt);
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
    public static function getRoleListHtml($data, $select_id = 0, $level = 0)
    {
        $html = '';
        foreach ($data as $value) {
            $text = str_repeat('&nbsp;', $level * 4) . $value['name'];
            $html .= sprintf('<option %s value="%s">%s</option>', $select_id == $value['id'] ? 'selected': '', $value['id'], $text);
            if (isset($value['childlist'])) $html .= self::getRoleListHtml($value['childlist'], $select_id, $level + 1);
        }
        return $html;
    }
}