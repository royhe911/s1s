<?php

namespace app\admin\model;

use think\Model;

class AuthGroup extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';


    public function getNameAttr($value, $data)
    {
        return __($value);
    }

    public static function getDataAll()
    {
        $dataAll = cache('auth_group_data_all_cc');

        if (empty($dataAll)) {
            $dataAll = self::select()->toArray();

            cache('auth_group_data_all_cc', $dataAll);
        }

        return $dataAll;
    }

    public static function getRole($gid = null)
    {
        return self::get($gid);
    }
}
