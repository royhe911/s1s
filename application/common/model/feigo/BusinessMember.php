<?php
namespace app\common\model\feigo;

use fast\Random;
use think\Exception;
use think\Model;

class BusinessMember extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];

    protected $table = 'feigo_business_member';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';

    /**
     * 用户注册登录
     * @param $mobile
     * @param $password
     * @param int $type
     * @param string $sms
     * @param string $username
     * @param string $device
     * @param float|int $expiry
     * @return mixed
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function login($mobile, $password, $type = 1, $sms = '',$username='', $device='1',$expiry = 3600 * 24 * 30)
    {
        if ($type == 6  && checkSms(5, $mobile, $sms) !== true) {
            throw new Exception(lang("20001"), '20001');
        }
        $ip = getIp();
        $time = date('Y-m-d H:i:s', time());
        $member_info = $this->where('mobile|username', $mobile)->find();
        if (empty($member_info)) {
            if ($type == 5) {
                throw new Exception(lang("50001"), '50001');
            }
            $salt = Random::alnum();
            $pass = '';
            if($password){
                $pass = self::encryptPassword($password, $salt);
            }
            $member_info = new BusinessMember();
            $member_info->mobile = $mobile;
            $member_info->nickname = substr_replace($mobile, '****', 3, 4);
            $member_info->password = $pass;
            $member_info->salt = $salt;
            $member_info->reg_ip = $ip;
            $member_info->status = 1;
            $member_info->avatar = '/member/default.jpg';
            $member_info->last_login_ip = $ip;
            $member_info->last_login_time = date("Y-m-d H:i:s");
            $member_info->create_time = $time;
            $member_info->update_time = $time;
            $member_info->username = $username ? $username:"";
            $member_info->device = $device;
        } else {
            if ($type == 5 ) { // 密码登录 校验密码
                if (self::encryptPassword($password, $member_info['salt']) != $member_info['password']) {
                    throw new Exception(lang("50004"), '50004');
                }
            }
            if ($member_info->status != 1) {
                throw new Exception(lang("50002"), '50002');
            }
            $member_info->password = self::encryptPassword($password, $member_info['salt']);
            $member_info->last_login_ip = $ip;
            $member_info->last_login_time = $time;
            // 查看当前有的门店
            $shop_id = BusinessShop::getMyShopId($member_info->id);
        }
        $member_info->save();
        $pw_status = $member_info['password'] ? 1 : 0;
        $member_info->hidden([ 'sex', 'birthday', 'contacts_info', 'email', 'reg_ip', 'status', 'region_id_1', 'region_id_2', 'region_id_3', 'create_time', 'update_time', 'last_login_time', 'last_login_ip', 'password', 'salt','device']);
        $token = $this->memberToken($member_info, $expiry);
        $member_info['username'] = $member_info['username'] ? $member_info['username']: $member_info['nickname'];
        $member_info['token'] = $token;
        $member_info['shop_id'] = isset($shop_id) ? $shop_id : 0;
        $member_info['pw_status'] = $pw_status;
        $member_info = $member_info->toArray();
        session("business_info", $member_info);
        return $member_info;
    }

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

    /**
     * 用户token
     * @param $member_info
     * @param int $expiry
     * @return bool|string
     */
    public static function memberToken($member_info, $expiry = 3600 * 24 * 30)
    {
        if (empty($member_info) || $member_info == false) {
            return false;
        }
        return authcode("{$member_info['id']}|{$member_info['mobile']}|{$member_info['last_login_ip']}", "ENCODE", config("auth_key"), $expiry);
    }

    /**
     * 修改绑定手机接口
     * @param 用户信息 $member_info
     * @param 短信 $sms_code
     * @param number $expiry
     * @throws Exception
     */
    public function changeMobile($member_info, $sms_code, $expiry = 3600)
    {
        if (checkSms(6, $member_info['mobile'], $sms_code) !== true) {
            throw new Exception(lang("20001"), '20001');
        }
        $ip = getIp();
        
        $member_info['last_login_ip'] = $ip;
        $member_info['last_login_time'] = date("Y-m-d H:i:s");
        self::update($member_info);

        $member_info['token'] = $this->memberToken($member_info);
        return $member_info;
    }

}
