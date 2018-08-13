<?php

namespace app\admin\model;

use think\facade\Cache;
use think\Model;

class AuthRule extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';
    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';

    protected static function init()
    {
        self::afterWrite(function ($row) {
            Cache::rm('__menu__');
        });
    }

    public function getTitleAttr($value, $data)
    {
        return __($value);
    }

    public static function getDataAll()
    {
        $dataAll = cache('auth_rule_data_all_cc');

        if (empty($dataAll)) {
            $dataAll = self::select()->toArray();

            cache('auth_rule_data_all_cc', $dataAll);
        }

        return $dataAll;
    }
}
