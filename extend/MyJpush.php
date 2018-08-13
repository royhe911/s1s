<?php

/**
 * 极光推送
 * Class MyJpush
 */
class MyJpush
{

    private $app_key        = '';            // appKey。
    private $master_secret  = '';            // 主密码
    private $url            = '';            // 推送的地址
    private $message_type   = 1;             // 消息类型 2-自定义
    static private $instance = null;

    /**
     * 若实例化的时候传入相应的值则按新的相应值进行
     * MyJpush constructor.
     * @param null $app_key
     * @param null $master_secret
     * @param null $url
     */
    public function __construct($app_key = null, $master_secret = null, $url = null)
    {
        $j_push = config('other_api.j_push');
        $this->app_key = $app_key ? $app_key : $j_push['app_key'];
        $this->master_secret = $master_secret ? $master_secret : $j_push['master_secret'];
        $this->url = $url ? $url : $j_push['url'];
    }

    /**
     * 初始化类
     * @return MyImage
     */
    static function instance()
    {
        if (empty(self::$instance)) {
            self::$instance = new MyJpush();
        }
        return self::$instance;
    }

    /**
     * @param string $receiver 接收者的信息
     * all 字符串 该产品下面的所有用户. 对app_key下的所有用户推送消息
     * tag(20个)Array标签组(并集): tag=>array('昆明','北京','曲靖','上海');
     * tag_and(20个)Array标签组(交集): tag_and=>array('广州','女');
     * alias(1000)Array别名(并集): alias=>array('1','2');
     * registration_id(1000)注册ID设备标识(并集): registration_id=>array('20effc07746e1ff2001bd80308a467d800bed39e');
     * @param string $title
     * @param string $content 推送的内容
     * @param $extras 附加参数
     * @param string $m_time 保存离线时间的秒数默认为一天(可不传)单位为秒
     * @return bool|mixed
     */
    public function push($receiver = 'all', $title = '', $content = '', $extras = [], $m_time = 86400)
    {
        $base64 = base64_encode("$this->app_key:$this->master_secret");
        $header = ["Authorization:Basic $base64", "Content-Type:application/json"];
        $data = [];
        $data['platform'] = 'all';          //目标用户终端手机的平台类型android,ios,winphone
        $data['audience'] = $receiver;          //目标用户
        // 发送通知
        if ($this->message_type == 1) {
            $data['notification'] = [
                // 统一的模式--标准模式
                // "alert"=>$content,
                // 安卓自定义
                "android" => [
                    "alert" => $content,
                    //"title" => $title,
                    "builder_id" => 1,
                    "extras" => $extras
                ],
                // ios的自定义
                "ios" => [
                    "alert" => $content,
                    //"title" => $title,
                    "badge" => "1",
                    "sound" => "default",
                    'mutable-content' => true, //设置true 支持ios10的推送
                    "content-available" => 1,
                    "extras" => $extras
                ],
            ];
        } else {
            // 自定义信息
            $data['message'] = [
                "title" => $title,
                "msg_content" => $content,
                "content-available" => 1,
                "sound" => 'default',
                "extras" => $extras
            ];
        }

        // 附加选项
        $data['options'] = [
            "sendno" => time(),
            "time_to_live" => $m_time,      //保存离线时间的秒数默认为一天
            "apns_production" => 1,        //指定 APNS 通知发送环境：0开发环境，1生产环境。
        ];
        $param = json_encode($data);

        if ($res = $this->push_curl($param, $header)) {
            return $res;
        } else {
            return false;
        }
    }

    /**
     * 推送的Curl方法
     * @param string $param
     * @param string $header
     * @return bool|mixed
     */
    public function push_curl($param = "", $header = "")
    {
        if (empty($param)) {
            return false;
        }
        $postUrl = $this->url;
        $curlPost = $param;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $postUrl);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
}