<?php

namespace app\merchant\controller;

use app\common\model\Admin;
use app\common\model\Merchant;
use fast\Random;
use think\Controller;
use think\Exception;
use think\facade\Log;
use think\facade\Request;
use think\facade\Session;

class Pub extends Controller
{
    protected $default_limit = 12;

    protected $default_limit1 = 24;

    protected $result = ['code' => "1", 'msg' => '操作成功', 'data' => null];

    public function __construct()
    {
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        $token = isset($_SERVER['HTTP_TOKEN']) ? $_SERVER['HTTP_TOKEN'] : '';

        if (!empty($token)) header('token:' . $token);

        header('Access-Control-Allow-Credentials:true');
        $allow_origin = config('other_api.allow_origin');

        if (in_array($origin, $allow_origin) || config('app_debug') == true) {
            header('Access-Control-Allow-Origin:' . $origin);
            header('Access-Control-Allow-Methods:POST,GET,OPTIONS,PUT,DELETE');
            header('Access-Control-Allow-Headers:x-requested-with,content-type,token,cityid');
        }else{
            exit("access error");
        }

        if ($_SERVER['REQUEST_METHOD'] == "OPTIONS") {
            exit();
        }
    }

    /**
     * 商户激活注册登录
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function invite_merchant()
    {
        $sign = request()->post('sign');
        if ($sign == null || empty($sign)) {
            Log::info('key值为空');
            return $this->result(null, "40001", lang("40001"));
        }
        $info = authcode($sign);
        $info = explode('|', $info);
        if (!isset($info[0]) || !isset($info[1])) {
            Log::info('key值解密不出来:' . $sign);
            return $this->result(null, "40001", lang("40001"));
        }
        $username = $info[0];
        $s_id = $info[1];
        $admin = Admin::where(['id' => $s_id, 'username' => $username])->find();
        if (empty($admin)) {
            Log::info('找不到记录:' . $sign);
            return $this->result(null, "40001", lang("40001"));
        }
        $mobile = request()->post('mobile');
        $pass = request()->post('pass');
        $open_id = request()->post('open_id');
        $wx_id = request()->post('wx_id');
        $nickname = request()->post('nickname');
        $headimgurl = request()->post('headimgurl');
        $salt = Random::alnum();
        $pass = Admin::encryptPassword($pass, $salt);

        $avatar = config("qiniu.img_prefix") . "merchant/" . base_convert(time() * 1000, 10, 36) . "_" . base_convert(microtime(), 10, 36) . uniqid() . ".jpg";
        \Qiniu::instance()->fetchFile($headimgurl, config("qiniu.bucket"), $avatar);

        $data = [
            'username' => $mobile,
            'mobile' => $mobile,
            'salt' => $salt,
            'avatar' => $avatar,
            'password' => $pass,
            'status' => 3,
            's_id' => $s_id,
            'open_id' => $open_id,
            'wx_id' => $wx_id,
            'nickname' => $nickname,
            'addtime' => time(),
            'updatetime' => time(),
        ];
        if (Merchant::insert($data)) {
            return $this->result;
        }
        $this->result['code'] = '20026';
        $this->result['msg'] = lang($this->result['code']);
        return $this->result;
    }

    /**
     * 获取登陆短信验证码
     * parameter mobile post
     * @return array
     */
    public function get_login_sms()
    {
        $mobile = Request::post('mobile');
        // 参数异常
        if (empty($mobile)) {
            $this->result['code'] = '10';
            $this->result['msg'] = lang($this->result['code']);
            return $this->result;
        }

        try {
            $code = sendSms(1, $mobile);
            // 获取短信验证码
            Log::info("sms_code:{$code}");

            if (config('app_debug')) {
                $this->result['data']['sms_code'] = $code;
            }
        } catch (Exception $e) {
            Log::error($e->getFile());
            Log::error("line:" . $e->getLine() . "," . $e->getMessage());
            $this->result['code'] = "{$e->getCode()}";
            $this->result['msg'] = lang($this->result['code']);
            return $this->result;
        }

        $this->result['msg'] = lang($this->result['code']);
        return $this->result;
    }

    /**
     * 退出登录
     * @return array
     */
    public function logout()
    {
        Session::delete('merchant_info');

        $this->result['code'] = '1';
        $this->result['msg'] = lang($this->result['code']);
        return $this->result;
    }

    /**
     * 获取七牛token
     * parameter type get
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get_token()
    {
        $member_info = $this->checkLogin();
        if ($member_info == false) {
            $this->result['code'] = '20000';
            $this->result['msg'] = lang($this->result['code']);
            return $this->result;
        }
        $pre = config("qiniu.img_prefix");

        $type = request()->get('type', 1);
        $zh = 'test';
        if ($type == 1) { // 用户头像
            $zh = "avatar/{$member_info['id']}/";
        }
        $key =  $pre . $zh . date("YmdHis") . mt_rand(10000, 999999);
        $token = \Qiniu::getUploadToken(3600, config("qiniu.bucket"), $key);

        $result_data['key'] = $key;
        $result_data['token'] = $token;
        $this->result['data'] = $result_data;
        $this->result['msg'] = lang($this->result['code']);
        return $this->result;
    }

    /**
     * 用户名密码登录
     * @return array
     */
    public function login()
    {
        $mobile = Request::post('mobile');
        $password = Request::post('password');
        try {
            // 数据正确性校验
            if (empty($mobile) || empty($password)) {
                throw new Exception(lang(10), "10");
            }
            //获取存储open信息
            $where = [['mobile', '=', $mobile]];
            $merchant_info = Merchant::master()->where($where)->find();
            if ($merchant_info->password != md5(md5($password) . $merchant_info->salt)) {
                throw new Exception(lang(10), "10");
            }
            if ($merchant_info['status'] != 1) {
                throw new Exception(lang(10), "10");
            }

            $merchant_info->hidden(['password']);
            $merchant_info = $merchant_info->toArray();
            $merchant_info['token'] = Merchant::memberToken($merchant_info);
            session("merchant_info", $merchant_info);

            $result_data['merchant_info'] = $merchant_info;
            $this->result['data'] = $result_data;
            return $this->result;
        } catch (Exception $e) {
            $this->result['code'] = "{$e->getCode()}";
            $this->result['msg'] = $e->getMessage();
            return $this->result;
        }
    }

    /**
     * 第三方登录微信
     * @return array
     */
    public function other_login()
    {
        $open_id = Request::post('open_id');
        try {
            // 数据正确性校验
            if (empty($open_id)) {
                throw new Exception(lang(10), "10");
            }
            //获取存储open信息
            $where = [['open_id', '=', $open_id]];
            $merchant_info = Merchant::master()->where($where)->find();
            if ($merchant_info['status'] != 1) {
                throw new Exception(lang(10), "10");
            }

            $merchant_info->hidden(['password']);
            $merchant_info = $merchant_info->toArray();
            $merchant_info['token'] = Merchant::memberToken($merchant_info);
            session("merchant_info", $merchant_info);

            $result_data['merchant_info'] = $merchant_info;
            $this->result['data'] = $result_data;
            return $this->result;
        } catch (Exception $e) {
            $this->result['code'] = "{$e->getCode()}";
            $this->result['msg'] = $e->getMessage();
            return $this->result;
        }
    }

    /**
     * 检查是否登录
     * @return array|bool|mixed|null|\PDOStatement|string|\think\Model
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    protected function checkLogin()
    {
        $token = isset($_SERVER['HTTP_TOKEN']) ? $_SERVER['HTTP_TOKEN'] : '';
        Log::info("token:{$token}");
        $merchant_info = session("merchant_info");

        if (!empty($merchant_info)) {
            return $merchant_info;
        } elseif (!empty($token)) {
            $merchant_str = authcode($token, "DECODE", config("auth_key"));
            Log::info("merchant_str:{$merchant_str}");
            $merchant_arr = explode("|", $merchant_str);
            $merchant_id = $merchant_arr[0] ?? '';
            $merchant_mobile = $merchant_arr[1] ?? '';
            if (!empty($merchant_id) && !empty($merchant_id)) {
                // 获取用户信息
                $merchant_info = Merchant::where('id', $merchant_id)->where('mobile', $merchant_mobile)->find();
                if (!empty($member_info)) {
                    $merchant_info->hidden(['password']);
                    $merchant_info = $merchant_info->toArray();
                    $merchant_info['token'] = $token;

                    session("merchant_info", $merchant_info);
                    return $merchant_info;
                }
            }
        }
        return false;
    }
}
