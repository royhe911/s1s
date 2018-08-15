<?php
namespace app\admin\controller\merchant;

use think\facade\Config;
use think\facade\Hook;
use think\Validate;

use app\admin\model\AdminLog;
use app\common\model\Merchant as MerchantM;
use app\common\controller\Backend;

/**
 * 后台首页
 * @internal
 */
class Merchant extends Backend
{
    protected $relationSearch = true;
    protected $layout = 'default2';

    /**
     * 后台首页
     */
    public function index()
    {
        $w = [];
        $keyword = request()->get('keyword');
        if ($keyword != null) $w[] = ['m.username|m.mobile|m.wx_id', 'like', '%' . $keyword . '%'];
        if (in_array(12, $this->auth->getGroupIds())) { // 业务员
            $w[] = ['a.id', '=', session('admin')['id']];
        }
        $list = (new MerchantM)
            ->alias('m')
            ->where($w)
            ->field('m.*, a.nickname')
            ->join('admin a', 'a.id=m.s_id', 'left')
            ->paginate($this->cur_limit, false, ['query' => $this->request->get()]);
        $this->view->assign('list', $list);
        return $this->view->fetch();
    }

    /**
     * 管理员登录
     */
    public function login()
    {
        $url = $this->request->get('url', 'index/index');
        if ($this->auth->isLogin())
        {
            return $this->redirect($url);
        }
        if ($this->request->isPost())
        {
            $username = $this->request->post('username');
            $password = $this->request->post('password');
            $keeplogin = $this->request->post('keeplogin');
            //$token = $this->request->post('__token__');
            $rule = [
                'username'  => 'require|length:3,30',
                'password'  => 'require|length:3,30',
                //'__token__' => 'token',
            ];
            $data = [
                'username'  => $username,
                'password'  => $password,
                //'__token__' => $token,
            ];
            if (Config::get('fastadmin.login_captcha'))
            {
                $rule['captcha'] = 'require|captcha';
                $data['captcha'] = $this->request->post('captcha');
            }
            $validate = new Validate($rule, [], ['username' => __('Username'), 'password' => __('Password'), 'captcha' => __('Captcha')]);
            $result = $validate->check($data);
            if (!$result)
            {
                return $this->result(null, 0, $validate->getError(), 'json');
            }
            AdminLog::setTitle(__('Login'));
            $result = $this->auth->login($username, $password, $keeplogin ? 86400 : 0);
            if ($result === true)
            {
                return $this->result(['url' => $url], 1, '登录成功', 'json');
            }
            else
            {
                $msg = $this->auth->getError();
                $msg = $msg ? $msg : '账号或密码输入有误';
                return $this->result(null, 0, $msg, 'json');
            }
        }

        // 根据客户端的cookie,判断是否可以自动登录
        if ($this->auth->autologin())
        {
            $this->redirect($url);
        }
        $background = cdnurl(Config::get('fastadmin.login_background'));
        $this->view->assign('background', $background);
        $this->view->assign('title', __('Login'));
        Hook::listen("login_init", $this->request);
        return $this->view->fetch();
    }

    /**
     * 注销登录
     */
    public function logout()
    {
        $this->auth->logout();
        return $this->redirect('index/login');
    }
}