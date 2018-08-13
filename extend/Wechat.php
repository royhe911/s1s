<?php

class Wechat
{

    private $config = [];
    
    // 单例
    private static $instance;

    public static function instance($config = [])
    {
        if (self::$instance === null) {
            self::$instance = new static($config);
        }
        return self::$instance;
    }

    /**
     * 构造方法，用于构造上传实例
     */
    public function __construct($config = [])
    {
        $this->config($config);
    }

    /**
     * 设置
     *
     * @param array $config
     * @return $this
     */
    public function config($config = [])
    {
        $this->config = array_merge($this->config, config("other_api.mp_wx_conf"));
    }

    private static $instance_manager;
    
    // 实例化wechat sdk
    public static function manager()
    {
        if (self::$instance_manager === null) {
            $config = get_object_vars(self::$instance);
            self::$instance_manager = new Jhtx\WechatSdk\Api($config['config']);
        }
        return self::$instance_manager;
    }

    public function get_authorize_url($redirect_uri)
    {
        $state = "QXG_10000";
        return self::manager()->get_authorize_url("snsapi_userinfo", $redirect_uri, $state);
    }

    public function get_userinfo_by_authorize()
    {
        return self::manager()->get_userinfo_by_authorize("snsapi_userinfo");
    }

    public function get_jsapi_ticket()
    {
        return self::manager()->get_jsapi_ticket();
    }

    /**
     * 获取jssdk 授权参数
     *
     * @param string $url
     * @param string $type
     * @param string $jsonp_callback
     */
    public function get_jsapi_config($url = '', $type = '', $jsonp_callback = 'callback')
    {
        $jsapi_ticket = cache("wechat_jsapi_ticket");
        if (empty($jsapi_ticket)) {
            $jsapi_ticket = self::manager()->get_jsapi_ticket();
            cache("wechat_jsapi_ticket", $jsapi_ticket, 7200);
        }
        $wechat_jsapi_config = self::manager()->get_jsapi_config($jsapi_ticket, $url, $type, $jsonp_callback);
        return $wechat_jsapi_config;
    }
}