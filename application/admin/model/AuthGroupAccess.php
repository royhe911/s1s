<?php

namespace app\admin\model;

use think\Model;

class AuthGroupAccess extends Model
{
    public static function getByUid($id)
    {
        $result = self::where('uid', $id)->field('group_id')->find();
        return $result ? $result['group_id'] : false;
    }
}
