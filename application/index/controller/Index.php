<?php
namespace app\index\controller;

use app\common\model\feigo\Admin;
use fast\Random;
use think\Controller;
use think\Exception;
use think\facade\Log;

class Index extends Controller
{
    public function index()
    {
        return $this->fetch('qxg/index.html');
    }
    public function connect()
    {
        return $this->fetch('qxg/connect.html');
    }

    /**
     * 管理员激活页面
     * @param null $sign
     * @return mixed
     * @throws Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function activation_merchant_view($sign =  null)
    {
        //header('Content-type:text/html; charset=utf-8');
        //echo authcode('12345678|110', 'ENCODE', '', 3600);die;
        try {
            if ($sign == null || empty($sign)) {
                Log::info('key值为空');
                throw new Exception(lang("40001"), 40001);
            }

            $info = authcode($sign);
            $info = explode('|', $info);

            if (!isset($info[0]) || !isset($info[1])) {
                Log::info('key值解密不出来:' . $sign);
                throw new Exception(lang("40001"), 40001);
            }

            $username = $info[0];
            $admin_id = $info[1];
            $admin = Admin::where(['id' => $admin_id, 'username' => $username])->find();

            if (empty($admin)) {
                Log::info('找不到记录:' . $sign);
                throw new Exception(lang("40001"), 40001);
            }

            if ($admin['status'] != 2) {
                Log::info('该记录已激活:' . $sign);
                throw new Exception(lang("40001"), 40001);
            }

            $data = ['username' => $username, 'sign' => $sign];

            if (isMobile()) return $this->fetch('h5_merchant_view', $data);

            return $this->fetch('pc_merchant_view', $data);
        } catch (Exception $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     * 管理员激活表单
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function activation_merchant()
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
        $admin_id = $info[1];
        $admin = Admin::where(['id' => $admin_id, 'username' => $username])->find();
        if (empty($admin)) {
            Log::info('找不到记录:' . $sign);
            return $this->result(null, "40001", lang("40001"));
        }
        if ($admin['status'] != 2) {
            Log::info('该记录已激活:' . $sign);
            return $this->result(null, "40001", lang("40001"));
        }
        $pass = request()->post('pass');
        $salt = Random::alnum();
        $pass = Admin::encryptPassword($pass, $salt);
        $data = [
            'salt' => $salt,
            'password' => $pass,
            'status' => 1,
            'nickname' => substr_replace($admin['mobile'], '****', 3, 4),
            'updatetime' => date('Y-m-d H:i:s', time()),
        ];
        if (Admin::where(['id' => $admin_id, 'username' => $username])->update($data)) {
            return $this->result(null, "1", lang("1"));
        }
        return $this->result(null, "0", lang("0"));
    }
}
