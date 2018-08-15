<?php

use app\admin\model\Category;
use fast\Form;
use fast\Tree;
use think\Db;

if (!function_exists('buildSelect')) {

    /**
     * 生成下拉列表
     * @param string $name
     * @param mixed $options
     * @param mixed $selected
     * @param mixed $attr
     * @return string
     */
    function buildSelect($name, $options, $selected = [], $attr = [])
    {
        $options  = is_array($options) ? $options : explode(',', $options);
        $selected = is_array($selected) ? $selected : explode(',', $selected);
        return Form::select($name, $options, $selected, $attr);
    }
}

if (!function_exists('buildRadios')) {

    /**
     * 生成单选按钮组
     * @param string $name
     * @param array $list
     * @param mixed $selected
     * @return string
     */
    function buildRadios($name, $list = [], $selected = null)
    {
        $html     = [];
        $selected = is_null($selected) ? key($list) : $selected;
        $selected = is_array($selected) ? $selected : explode(',', $selected);
        foreach ($list as $k => $v) {
            $html[] = sprintf(Form::label("{$name}-{$k}", "%s {$v}"), Form::radio($name, $k, in_array($k, $selected), ['id' => "{$name}-{$k}"]));
        }
        return '<div class="radio">' . implode(' ', $html) . '</div>';
    }
}

if (!function_exists('buildCheckboxs')) {

    /**
     * 生成复选按钮组
     * @param string $name
     * @param array $list
     * @param mixed $selected
     * @return string
     */
    function buildCheckboxs($name, $list = [], $selected = null)
    {
        $html     = [];
        $selected = is_null($selected) ? [] : $selected;
        $selected = is_array($selected) ? $selected : explode(',', $selected);
        foreach ($list as $k => $v) {
            $html[] = sprintf(Form::label("{$name}-{$k}", "%s {$v}"), Form::checkbox($name, $k, in_array($k, $selected), ['id' => "{$name}-{$k}"]));
        }
        return '<div class="checkbox">' . implode(' ', $html) . '</div>';
    }
}

if (!function_exists('buildCategorySelect')) {

    /**
     * 生成分类下拉列表框
     * @param string $name
     * @param string $type
     * @param mixed $selected
     * @param array $attr
     * @return string
     */
    function buildCategorySelect($name, $type, $selected = null, $attr = [], $header = [])
    {
        $tree = Tree::instance();
        $tree->init(Category::getCategoryArray($type), 'pid');
        $categorylist = $tree->getTreeList($tree->getTreeArray(0), 'name');
        $categorydata = $header ? $header : [];
        foreach ($categorylist as $k => $v) {
            $categorydata[$v['id']] = $v['name'];
        }
        $attr = array_merge(['id' => "c-{$name}", 'class' => 'form-control selectpicker'], $attr);
        return buildSelect($name, $categorydata, $selected, $attr);
    }
}

if (!function_exists('buildToolbar')) {

    /**
     * 生成表格操作按钮栏
     * @param array $btns 按钮组
     * @param array $attr 按钮属性值
     * @return string
     */
    function buildToolbar($btns = null, $attr = [])
    {
        $auth       = \app\admin\library\Auth::instance();
        $controller = str_replace('.', '/', strtolower(think\facade\Request::instance()->controller()));
        $btns       = $btns ? $btns : ['refresh', 'add', 'edit', 'del', 'import', 'status_enable', 'status_disable'];
        $btns       = is_array($btns) ? $btns : explode(',', $btns);
        $index      = array_search('delete', $btns);
        if ($index !== false) {
            $btns[$index] = 'del';
        }
        $btnAttr = [
            'refresh'        => ['javascript:;', 'btn btn-primary btn-refresh', 'fa fa-refresh', '', __('Refresh')],
            'add'            => ['javascript:;', 'btn btn-success btn-add', 'fa fa-plus', __('Add'), __('Add')],
            'edit'           => ['javascript:;', 'btn btn-success btn-edit btn-disabled disabled', 'fa fa-pencil', __('Edit'), __('Edit')],
            'del'            => ['javascript:;', 'btn btn-danger btn-del btn-disabled disabled', 'fa fa-trash', __('Delete'), __('Delete')],
            'import'         => ['javascript:;', 'btn btn-danger btn-import', 'fa fa-upload', __('Import'), __('Import')],
            'status_enable'  => ['javascript:;', 'btn btn-success btn-status-enable btn-disabled disabled', 'fa fa-trash', __('StatusEnable'), __('StatusEnable')],
            'status_disable' => ['javascript:;', 'btn btn-success btn-status-disable btn-disabled disabled', 'fa fa-trash', __('StatusDisable'), __('StatusDisable')],
        ];
        $btnAttr = array_merge($btnAttr, $attr);
        $html    = [];
        foreach ($btns as $k => $v) {
            //如果未定义或没有权限
            if (!isset($btnAttr[$v]) || ($v !== 'refresh' && !$auth->check("{$controller}/{$v}"))) {
                continue;
            }
            list($href, $class, $icon, $text, $title) = $btnAttr[$v];
            $extend                                   = $v == 'import' ? 'id="btn-import-file" data-url="ajax/upload" data-mimetype="csv,xls,xlsx" data-multiple="false"' : '';
            $html[]                                   = '<a href="' . $href . '" class="' . $class . '" title="' . $title . '" ' . $extend . '><i class="' . $icon . '"></i> ' . $text . '</a>';
        }
        return implode(' ', $html);
    }
}

if (!function_exists('buildHeading')) {

    /**
     * 生成页面Heading
     *
     * @param string $path 指定的path
     * @return string
     */
    function buildHeading($path = null, $container = true)
    {
        $title = $content = '';
        if (is_null($path)) {
            $action     = request()->action();
            $controller = str_replace('.', '/', request()->controller());
            $path       = strtolower($controller . ($action && $action != 'index' ? '/' . $action : ''));
        }
        // 根据当前的URI自动匹配父节点的标题和备注
        $data = Db::name('auth_rule')->where('name', $path)->field('title,remark')->find();
        if ($data) {
            $title   = __($data['title']);
            $content = __($data['remark']);
        }
//        if (!$content)
        //            return '';
        $result = '<div class="panel-lead"><em>' . $title . '</em>' . $content . '</div>';
        if ($container) {
            $result = '<div class="panel-heading">' . $result . '</div>';
        }
        return $result;
    }
}

if (!function_exists('rmdirs')) {

    /**
     * 删除文件夹
     * @param string $dirname 目录
     * @param bool $withself 是否删除自身
     * @return boolean
     */
    function rmdirs($dirname, $withself = true)
    {
        if (!is_dir($dirname)) {
            return false;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirname, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        if ($withself) {
            @rmdir($dirname);
        }
        return true;
    }
}

if (!function_exists('cdnurl')) {

    /**
     * 获取上传资源的CDN的地址
     * @param string $url 资源相对地址
     * @return string
     */
    function cdnurl($url)
    {
        return preg_match("/^https?:\/\/(.*)/i", $url) ? $url : \think\facade\Config::get('upload.cdnurl') . $url;
    }
}

if (!function_exists('sendsms')) {
    /**
     * 发送短信
     * @param  string  $to    手机号码集合，多个用英文逗号分开
     * @param  array   $data  内容数据 格式为数组 例如：array('Marry','Alon')，如不需替换请填 null
     * @param  integer $temId 模板Id,测试应用和未上线应用使用测试模板请填写1，正式应用上线后填写已申请审核通过的模板ID
     * @param  string  $appId 应用Id，在官网应用列表中点击应用，对应应用详情中的APP ID
     * @return bool           返回发送结果
     */
    function sendsms($to = '', $data = [], $temId = 1, $appId = '8a216da85e6fff2b015e79280b69054c')
    {
        // 主帐号,对应开官网发者主账号下的 ACCOUNT SID
        $accountSid = '8a216da85e6fff2b015e79280b1a0547';
        // 主帐号令牌,对应官网开发者主账号下的 AUTH TOKEN
        $accountToken = '993ee1fa02e7418d8d48f07a79b0eab3';
        //沙盒环境（用于应用开发调试）：sandboxapp.cloopen.com
        //生产环境（用户应用上线使用）：app.cloopen.com
        $serverIP = 'app.cloopen.com';
        //请求端口，生产环境和沙盒环境一致
        $serverPort = '8883';

        //REST版本号，在官网文档REST介绍中获得。
        $softVersion = '2013-12-26';
        $rest        = new \Org\Net\REST($serverIP, $serverPort, $softVersion);
        $rest->setAccount($accountSid, $accountToken);
        $rest->setAppId($appId);
        // 发送模板短信
        $result = $rest->sendTemplateSMS($to, $datas, $tempId);
        if (empty($result) || $result->statusCode != 0) {
            return false;
        }
        return true;
    }
}
