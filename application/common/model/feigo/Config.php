<?php
namespace app\common\model\feigo;

use think\Model;

class Config extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];

    protected $table = 'feigo_config';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';

    /**
     * 获取键值
     * @param $key
     * @return mixed
     */
    public static function getValue($key)
    {
        $value = cache('config_value_' . $key);
        if (empty($value) && $value == false) {
            $value = self::where('c_key', $key)->value('c_value');
            if (!empty($value)) {
                $value = json_decode($value, true);
                cache('config_value_' . $key, $value, 3600);
            }
        }
        return $value;
    }
}
