<?php
namespace app\common\model\feigo;

use think\Model;

class Admin extends Model
{
    protected $connection = [
        'database'    => 's1s_admin',
    ];

    protected $table = 's1s_admin';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';

    /**
     * 密码加密
     * @param $password
     * @param string $salt
     * @param string $encrypt
     * @return mixed
     */
    public static function encryptPassword($password, $salt = '', $encrypt = 'md5')
    {
        return $encrypt($encrypt($password) . $salt);
    }
}
