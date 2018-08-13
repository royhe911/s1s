<?php
use think\facade\Log;
use Aliyun\Core\Profile\DefaultProfile;
use Aliyun\Core\DefaultAcsClient;
use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;
use Aliyun\Api\Sms\Request\V20170525\QuerySendDetailsRequest;
use Aliyun\Api\Dyvms\Request\V20170525\SingleCallByTtsRequest;

class Alisms
{

    static $config = array();

    static $acsClient = null;

    public static function getConfig()
    {
        if (empty(self::$config)) {
            self::$config = config("other_api.alisms");
        }
        return self::$config;
    }

    public function __construct()
    {
        $this->getConfig();
    }

    /**
     * 取得AcsClient
     *
     * @return DefaultAcsClient
     */
    public static function getAcsClient()
    {
        // 产品名称:云通信流量服务API产品,开发者无需替换
        $product = "Dysmsapi";
        
        // 产品域名,开发者无需替换
        $domain = "dysmsapi.aliyuncs.com";
        
        // TODO 此处需要替换成开发者自己的AK (https://ak-console.aliyun.com/)
        $accessKeyId = self::$config['accessId']; // AccessKeyId
        
        $accessKeySecret = self::$config['accessKey']; // AccessKeySecret
                                                       
        // 暂时不支持多Region
        $region = "cn-shenzhen";
        
        // 服务结点
        $endPointName = "cn-shenzhen";
        
        if (static::$acsClient == null) {
            \Aliyun\Core\Config::load();
            
            // 初始化acsClient,暂不支持region化
            $profile = DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);
            
            // 增加服务结点
            DefaultProfile::addEndpoint($endPointName, $region, $product, $domain);
            
            // 初始化AcsClient用于发起请求
            static::$acsClient = new DefaultAcsClient($profile);
        }
        return static::$acsClient;
    }
    

    /**
     * 取得AcsClient
     *
     * @return DefaultAcsClient
     */
    public static function getVmsAcsClient()
    {
        //产品名称:云通信流量服务API产品,开发者无需替换
        $product = "Dyvmsapi";
        
        //产品域名,开发者无需替换
        $domain = "dyvmsapi.aliyuncs.com";
        
        // TODO 此处需要替换成开发者自己的AK (https://ak-console.aliyun.com/)
        $accessKeyId = self::$config['accessId']; // AccessKeyId

        $accessKeySecret = self::$config['accessKey']; // AccessKeySecret

        
        // 暂时不支持多Region
        $region = "cn-hangzhou";
        
        // 服务结点
        $endPointName = "cn-hangzhou";
        
        
        if(static::$acsClient == null) {
            \Aliyun\Core\Config::load();
            
            //初始化acsClient,暂不支持region化
            $profile = DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);
        
            // 增加服务结点
            DefaultProfile::addEndpoint($endPointName, $region, $product, $domain);
        
            // 初始化AcsClient用于发起请求
            static::$acsClient = new DefaultAcsClient($profile);
        }
        return static::$acsClient;
    }

    /**
     * 发送短信
     *
     * @return stdClass
     */
    public static function sendSms($mobile, $type = 1, $data)
    {
        Log::write("send_sms\n type:{$type}\n mobile:{$mobile}", "log");
        Log::write($data, "log");
        if (empty($mobile)) {
            return false;
        }
        self::getConfig();
        
        // 初始化SendSmsRequest实例用于设置发送短信的参数
        $request = new SendSmsRequest();
        
        // 必填，设置短信接收号码
        $request->setPhoneNumbers($mobile);
        
        // 必填，设置签名名称，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        $request->setSignName(self::$config['signName']);
        
        $templateCode = null;
        $sms_data = null;
        if ($type == 1) {
            // 登陆
            $templateCode = "SMS_124745187";
            $sms_data['code'] = "{$data['code']}";
        } elseif ($type == 2) {
            $templateCode = "SMS_124745189";
            $sms_data['code'] = "{$data['code']}";
        }  elseif ($type == 3) {
            $templateCode = "SMS_132386150";
            //$sms_data['code'] = "{$data['code']}";
        }  elseif ($type == 4) {
            $templateCode = "SMS_132700046";
            $sms_data['password'] = "{$data['password']}";
        } elseif($type == 5){
            $templateCode = "SMS_137658929";
            $sms_data['code'] = "{$data['code']}";
        }elseif($type == 6){
            $templateCode = "SMS_137673925";
            $sms_data['code'] = "{$data['code']}";
        }elseif ($type == 7) {
            $templateCode = "SMS_137668864";
        }else{
            return false;
        }
        
        // 必填，设置模板CODE，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $request->setTemplateCode($templateCode);
        
        // 可选，设置模板参数, 假如模板中存在变量需要替换则为必填项
        $request->setTemplateParam(json_encode($sms_data, JSON_UNESCAPED_UNICODE));
        
        // 可选，设置流水号
        // $request->setOutId("yourOutId");
        
        // 选填，上行短信扩展码（扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段）
        // $request->setSmsUpExtendCode("1234567");
        
        // 发起访问请求
        $acsResponse = static::getAcsClient()->getAcsResponse($request);
        
        return $acsResponse;
    }
    

    /**
     * 文本转语音外呼
     *
     * @return stdClass
     * @throws ClientException
     */
    public static function sendVms($mobile, $type = 1, $data) {

        Log::write("send_sms\n type:{$type}\n mobile:{$mobile}", "log");
        Log::write($data, "log");
        if (empty($mobile)) {
            return false;
        }
        self::getConfig();
        
        //组装请求对象-具体描述见控制台-文档部分内容
        $request = new SingleCallByTtsRequest();        //必填-被叫显号
        $request->setCalledShowNumber("075536654828");
        //选填-音量
        $request->setVolume(100);
        //选填-播放次数
        $request->setPlayTimes(3);
        //选填-外呼流水号
        //$request->setOutId("yourOutId");
        

        $templateCode = null;
        $sms_data = null;
        if ($type == 1) {
            // 登陆
            $templateCode = "TTS_135807737";
            $sms_data['code'] = "{$data['code']}";
        }elseif ($type == 2) {
            $templateCode = "TTS_135807737";
            $sms_data['code'] = "{$data['code']}";
        } elseif($type == 5){
            $templateCode = "SMS_137658929";
            $sms_data['code'] = "{$data['code']}";
        }elseif($type == 6){
            $templateCode = "SMS_137673925";
            $sms_data['code'] = "{$data['code']}";
        }else{
            return false;
        }
        //必填-被叫号码
        $request->setCalledNumber($mobile);
        //必填-Tts模板Code
        $request->setTtsCode($templateCode);
        //选填-Tts模板中的变量替换JSON,假如Tts模板中存在变量，则此处必填
        $request->setTtsParam(json_encode($sms_data, JSON_UNESCAPED_UNICODE));
    
        //hint 此处可能会抛出异常，注意catch
        $response = static::getVmsAcsClient()->getAcsResponse($request);
        return $response;
    }
    

    /**
     * 短信发送记录查询
     *
     * @return stdClass
     */
    public static function querySendDetails()
    {
        
        // 初始化QuerySendDetailsRequest实例用于设置短信查询的参数
        $request = new QuerySendDetailsRequest();
        
        // 必填，短信接收号码
        $request->setPhoneNumber("12345678901");
        
        // 必填，短信发送日期，格式Ymd，支持近30天记录查询
        $request->setSendDate("20170718");
        
        // 必填，分页大小
        $request->setPageSize(10);
        
        // 必填，当前页码
        $request->setCurrentPage(1);
        
        // 选填，短信发送流水号
        $request->setBizId("yourBizId");
        
        // 发起访问请求
        $acsResponse = static::getAcsClient()->getAcsResponse($request);
        
        return $acsResponse;
    }
}
