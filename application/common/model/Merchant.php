<?php

namespace app\common\model;

use think\Model;

class Merchant extends Model
{
    protected $connection = [
        'database'    => 's1s_new',
    ];

    protected $table = 's1s_merchant';

    /**
     * 商户token
     * @param $member_info
     * @param int $expiry
     * @return bool|string
     */
    public static function memberToken($merchant_info, $expiry = 86400 * 7)
    {
        if (empty($merchant_info) || $merchant_info == false) {
            return false;
        }
        return authcode("{$merchant_info['id']}|{$merchant_info['mobile']}}", "ENCODE", config("auth_key"), $expiry);
    }
}
