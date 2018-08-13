<?php
namespace app\admin\controller;

use think\facade\Config;
use think\facade\Hook;
use think\Validate;

use app\admin\model\AdminLog;
use app\common\controller\Backend;

/**
 * 后台首页
 * @internal
 */
class Index extends Backend
{
    protected $noNeedLogin = ['login'];
    protected $noNeedRight = ['index', 'logout'];
    protected $layout = '';

    /**
     * 后台首页
     */
    public function index()
    {
        parent::initialize();
        //左侧菜单
        $menulist = $this->auth->getSidebar([
            'dashboard' => 'hot',
            'addon'     => ['new', 'red', 'badge'],
            'auth/rule' => __('Menu'),
            'general'   => ['new', 'purple'],
        ], $this->view->site['fixedpage']);
        $action = $this->request->request('action');
        if ($this->request->isPost())
        {
            if ($action == 'refreshmenu')
            {
                $this->success('', null, ['menulist' => $menulist]);
            }
        }
        $this->view->assign('menulist', $menulist);
        $this->view->assign('title', __('Home'));
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