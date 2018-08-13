<?php
namespace app\common\model\feigo;

use think\Model;
use think\Exception;
use think\facade\Log;

class Members extends Model
{
    protected $connection = [
        'database'    => 'feigo',
    ];

    protected $table = 'feigo_members';

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'timestamp';

    /**
     * 修改绑定手机接口
     * @param 用户信息 $member_info
     * @param 短信 $sms_code
     * @param number $expiry
     * @throws Exception
     */
    public function changeMobile($member_info, $sms_code, $expiry = 3600)
    {
        if (checkSms(2, $member_info['mobile'], $sms_code) !== true) {
            throw new Exception(lang("20001"), '20001');
        }
        $ip = getIp();
        
        $member_info['last_login_ip'] = $ip;
        $member_info['last_login_time'] = date("Y-m-d H:i:s");
        self::update($member_info,['id'=>$member_info['id']]);
        $member_info['token'] = $this->memberToken($member_info);
        return $member_info;
    }

    /**
     * @param string $mobile 手机号
     * @param string $sms 验证码
     * @param string $open_id 授权id
     * @param integer $expiry 登陆时效 3600秒
     * @throws Exception 验证失败
     * @return Members
     */
    public function smsLogin($mobile, $sms, $openid = null, $open_type=3,$expiry = 3600)
    {
        if (checkSms(1, $mobile, $sms) !== true) {
            throw new Exception(lang("20001"), '20001');
        }
        
        $ip = getIp();
        $member_info = $this->where('mobile', $mobile)->find();

        if (empty($member_info)) {
            $member_info = new Members();
            $member_info->mobile = $mobile;
            $member_info->nickname = substr_replace($mobile, '****', 3, 4);
            $member_info->password = $mobile . "_default_pwd";
            $member_info->reg_ip = $ip;
            $member_info->status = 1;
            $member_info->is_processor = 0;
            $member_info->avatar = '/member/default.jpg';
            $member_info->last_login_ip = $ip;
            $member_info->last_login_time = date("Y-m-d H:i:s");
        } else {
            $member_info->last_login_ip = $ip;
            $member_info->last_login_time = date("Y-m-d H:i:s");
        }
        
        if (! empty($openid)) {
            $member_data = cache("wechat_openid_{$open_type}_{$openid}");
            Log::info("wechat_openid_{$open_type}_{$openid}");
            Log::info($member_data);
            if (($member_info->avatar == '/member/default.jpg' || empty($member_info->avatar)) && ! empty($member_data['avatar'])) {
                $member_info->avatar = $member_data['avatar'];
                $member_info->sex = $member_data['sex'];
                $member_info->nickname = $member_data['nickname'];
            }
        }

        $member_info->save();
        
        if (! empty($openid)) {
            $member_data = cache("wechat_openid_{$open_type}_{$openid}");
            Log::info("wechat_openid_{$open_type}_{$openid}");
            Log::info($member_data);
            if (! empty($member_data['member_open_id'])) {
                MemberOpen::update(['member_id' => $member_info->id], ['id' => $member_data['member_open_id']]);
            }
        }

        $member_info->hidden(['password']);
        $member_info=$member_info->toArray();
        $member_info['token'] = $this->memberToken($member_info);
        return $member_info;
    }

    /**
     * 获取收藏列表
     * @param $member_id
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getFavoritesList($member_id)
    {
        $favorites_goods = FavoritesGood::where('member_id', $member_id)->column('good_id');
        $favorites_shops = FavoritesShop::where('member_id', $member_id)->column('shop_id');
        $data['favorites_goods'] = ",";
        $data['favorites_shops'] = ",";
        if (!empty($favorites_goods)) {
            $data['favorites_goods'] = implode(',', $favorites_goods);
            $data['favorites_goods'] = ",{$data['favorites_goods']},";
        }
        if (!empty($favorites_shops)) {
            $data['favorites_shops'] = implode(',', $favorites_shops);
            $data['favorites_shops'] = ",{$data['favorites_shops']},";
        }
        return $data;
    }

    /**
     * 用户token
     * @param $member_info
     * @param int $expiry
     * @return bool|string
     */
    public static function memberToken($member_info, $expiry = 86400 * 7)
    {
        if (empty($member_info) || $member_info == false) {
            return false;
        }
        return authcode("{$member_info['id']}|{$member_info['mobile']}|{$member_info['last_login_ip']}", "ENCODE", config("auth_key"), $expiry);
    }
}
