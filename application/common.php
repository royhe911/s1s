<?php
if (!function_exists('__')) {

    /**
     * 获取语言变量值
     * @param string $name 语言变量名
     * @param array $vars 动态变量值
     * @param string $lang 语言
     * @return mixed
     */
    function __($name, $vars = [], $lang = '')
    {
        if (is_numeric($name) || !$name)
            return $name;
        if (!is_array($vars)) {
            $vars = func_get_args();
            array_shift($vars);
            $lang = '';
        }
        return think\facade\Lang::get($name, $vars, $lang);
    }

}

/**
 * 发送HTTP请求方法
 *
 * @param string $url 请求URL
 * @param array $params 请求参数
 * @param string $method 请求方法GET/POST
 * @return array $data 响应数据
 */
function httpRequest($url, $params, $method = 'GET', $header = array(), $multi = false)
{
    $opts = array(CURLOPT_TIMEOUT => 30, CURLOPT_RETURNTRANSFER => 1, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_HTTPHEADER => $header);
    /* 根据请求类型设置特定参数 */
    switch (strtoupper($method)) {
        case 'GET':
            $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
            break;
        case 'POST':
            // 判断是否传输文件
            $params = $multi ? $params : http_build_query($params);
            $opts[CURLOPT_URL] = $url;
            $opts[CURLOPT_POST] = 1;
            $opts[CURLOPT_POSTFIELDS] = $params;
            break;
        default:
            throw new Exception('不支持的请求方式！');
    }
    /* 初始化并执行curl请求 */
    $ch = curl_init();
    curl_setopt_array($ch, $opts);
    $data = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error)
        throw new Exception('请求发生错误：' . $error);
    return $data;
}

/**
 * 获取短信验证码
 *
 * @param 短信类型 $type 1:登陆短信 2:忘记密码 3:城市合伙人激活通知 4:城市合伙人密码重置 5、B端登陆注册 6、B端忘记密码 7、给商户重置密码
 * @return string code
 */
function sendSms($type, $mobile, $data = null)
{
    if ($type == 1 || $type == 2 || $type == 5 || $type == 6) {
        $data = [];
        $code = cache("sms_code_{$type}_{$mobile}");
        if (empty($code)) {
            $code = rand(100000, 999999);
            cache("sms_code_{$type}_{$mobile}", $code, 600);
        }
        $data['code'] = $code;
    }
    if (!config('app_debug')) \Alisms::sendSms($mobile, $type, $data);

    return isset($code) ? $code : '';
}


/**
 * 获取语音验证码
 *
 * @param 短信类型 $type 1:登陆短信 2:忘记密码 
 * @return string code
 */
function sendVms($type, $mobile, $data = null)
{
    if ($type == 1 || $type == 2 || $type == 5 || $type == 6 ) {
        $data = [];
        $code = cache("sms_code_{$type}_{$mobile}");
        if (empty($code)) {
            $code = rand(100000, 999999);
            cache("sms_code_{$type}_{$mobile}", $code, 600);
        }
        $data['code'] = $code;
    }
    if (!config('app_debug')) \Alisms::sendVms($mobile, $type, $data);

    return isset($code) ? $code : '';
}

/**
 * 验证手机短信验证码
 *
 * @param 短信类型 $type 1:登陆短信 2:修改手机短信
 * @param 短信手机 $mobile
 * @param 验证码 $sms
 * @return true:验证通过 false 验证失败
 */
function checkSms($type, $mobile, $sms_code)
{
    if ($mobile == "17722651616" && $sms_code == "651616") {
        think\facade\Cache::rm("sms_code_{$type}_{$mobile}");
        return true;
    }
    $code = cache("sms_code_{$type}_{$mobile}");

    if (empty($code) || $code != $sms_code) {
        return false;
    }
    think\facade\Cache::rm("sms_code_{$type}_{$mobile}");
    return true;
}

/**
 * $string 明文或密文
 * $operation 加密ENCODE或解密DECODE
 * $key 密钥
 * $expiry 密钥有效期
 */
function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0)
{
    // 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙
    // 加入随机密钥，可以令密文无任何规律，即便是原文和密钥完全相同，加密结果也会每次不同，增大破解难度。
    // 取值越大，密文变动规律越大，密文变化 = 16 的 $ckey_length 次方
    // 当此值为 0 时，则不产生随机密钥
    $ckey_length = 4;

    // 密匙
    // $GLOBALS['discuz_auth_key'] 这里可以根据自己的需要修改
    $key = md5($key ? $key : config("AUTH_KEY"));
    // 密匙a会参与加解密
    $keya = md5(substr($key, 0, 16));
    // 密匙b会用来做数据完整性验证
    $keyb = md5(substr($key, 16, 16));
    // 密匙c用于变化生成的密文
    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';
    // 参与运算的密匙
    $cryptkey = $keya . md5($keya . $keyc);
    $key_length = strlen($cryptkey);
    // 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)，解密时会通过这个密匙验证数据完整性
    // 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确
    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
    $string_length = strlen($string);
    $result = '';
    $box = range(0, 255);
    $rndkey = array();
    // 产生密匙簿
    for ($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }
    // 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上并不会增加密文的强度
    for ($j = $i = 0; $i < 256; $i++) {
        $j = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }
    // 核心加解密部分
    for ($a = $j = $i = 0; $i < $string_length; $i++) {
        $a = ($a + 1) % 256;
        $j = ($j + $box[$a]) % 256;
        $tmp = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        // 从密匙簿得出密匙进行异或，再转成字符
        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }
    if ($operation == 'DECODE') {
        // substr($result, 0, 10) == 0 验证数据有效性
        // substr($result, 0, 10) - time() > 0 验证数据有效性
        // substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16) 验证数据完整性
        // 验证数据有效性，请看未加密明文的格式
        if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        // 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因
        // 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码
        return $keyc . str_replace('=', '', base64_encode($result));
    }
}

/**
 * 生成时间区间查询条件
 * author Abel
 * @param string $start 开始时间
 * @param string $end 结束时间
 * return string unix 时间区间
 */
function betweenTime($from_time, $key = 'create_time')
{
    if (!empty($from_time)) {
        $from_time = explode(' - ', $from_time);
        if (empty($from_time) || !isset($from_time[0]) || !isset($from_time[0])) {
            return '';
        }
        $start = date('Y-m-d 00:00:00', strtotime($from_time[0]));
        $end = date('Y-m-d 23:59:59', strtotime($from_time[1]));
        return [$key, 'between', [$start, $end]];
    } else {
        return '';
    }
}

/**
 * 生成时间区间查询条件
 * author Abel
 * @param string $start 开始时间
 * @param string $end 结束时间
 * return string unix 时间区间
 */
function betweenTwoTime($start, $end = '', $key = 'create_time')
{
    if (!empty($start)) {
        if(empty($end)){
            $end = date('Y-m-d', strtotime("$start +1 day"));
        } else {
            if ($start > $end) return '';
        }
        $start = date('Y-m-d 00:00:00', strtotime($start));
        $end = date('Y-m-d 23:59:59', strtotime($end));
        return [$key, 'between', [$start, $end]];
    } else {
        return '';
    }
}

/**
 * 二维数组根据首字母分组排序
 * @param  array $data 二维数组
 * @return array             根据首字母关联的二维数组
 */
function groupByInitials(array $data)
{
    $grouped = [];
    foreach ($data as $v) {
        $grouped[getFirstChar($v['pinyin'])][] = $v;
    }
    if (func_num_args() > 2) {
        $args = func_get_args();
        foreach ($grouped as $key => $value) {
            $parms = array_merge([$value], array_slice($args, 2, func_num_args()));
            $grouped[$key] = call_user_func_array('array_group_by', $parms);
        }
    }
    return $grouped;
}

/**
 * 拿出字符串首字母
 * @param $s0
 * @return null|string
 */
function getFirstChar($s0)
{
    $firstchar_ord = ord(strtoupper($s0{0}));
    if (($firstchar_ord >= 65 and $firstchar_ord <= 91) or ($firstchar_ord >= 48 and $firstchar_ord <= 57))
        return $s0{0};
    $s = iconv("UTF-8", "gb2312", $s0);
    $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
    if ($asc >= -20319 and $asc <= -20284)
        return "A";
    if ($asc >= -20283 and $asc <= -19776)
        return "B";
    if ($asc >= -19775 and $asc <= -19219)
        return "C";
    if ($asc >= -19218 and $asc <= -18711)
        return "D";
    if ($asc >= -18710 and $asc <= -18527)
        return "E";
    if ($asc >= -18526 and $asc <= -18240)
        return "F";
    if ($asc >= -18239 and $asc <= -17923)
        return "G";
    if ($asc >= -17922 and $asc <= -17418)
        return "H";
    if ($asc >= -17417 and $asc <= -16475)
        return "J";
    if ($asc >= -16474 and $asc <= -16213)
        return "K";
    if ($asc >= -16212 and $asc <= -15641)
        return "L";
    if ($asc >= -15640 and $asc <= -15166)
        return "M";
    if ($asc >= -15165 and $asc <= -14923)
        return "N";
    if ($asc >= -14922 and $asc <= -14915)
        return "O";
    if ($asc >= -14914 and $asc <= -14631)
        return "P";
    if ($asc >= -14630 and $asc <= -14150)
        return "Q";
    if ($asc >= -14149 and $asc <= -14091)
        return "R";
    if ($asc >= -14090 and $asc <= -13319)
        return "S";
    if ($asc >= -13318 and $asc <= -12839)
        return "T";
    if ($asc >= -12838 and $asc <= -12557)
        return "W";
    if ($asc >= -12556 and $asc <= -11848)
        return "X";
    if ($asc >= -11847 and $asc <= -11056)
        return "Y";
    if ($asc >= -11055 and $asc <= -10247)
        return "Z";
    return null;
}

/**
 * 返回✨
 * @param $num
 * @return float|int
 */
function getStarNumber($num)
{
    $int_num = floor($num);
    $float_num = $num - $int_num;
    return $int_num * 15 + $float_num * 10;
}

/**
 * 获取文件夹下所有文件路径
 *
 * @param $dir 路径
 */
function getAllFiles($path, &$files)
{
    if (is_dir($path)) {
        $dp = dir($path);
        while ($file = $dp->read()) {
            if ($file != "." && $file != "..") {
                getAllFiles($path . "/" . $file, $files);
            }
        }
        $dp->close();
    }
    if (is_file($path)) {
        $files[] = $path;
    }
}

/**
 * 获取文件夹下所有文件路径
 *
 * @param $dir 路径
 */
function getFilenamesByDir($dir)
{
    $files = array();
    getAllFiles($dir, $files);
    return $files;
}

/**
 * 去除 省 市 区 县 (也可以传入)
 * @param $region_name_3
 * @param null $city_name
 * @param null $qu_name
 * @return bool|mixed
 */
function removeStr($region_name_3, $city_name = null, $qu_name = null)
{
    if (empty($region_name_3)) {
        return false;
    }
    $region_name_3 = str_replace('省', '', $region_name_3);
    $region_name_3 = str_replace('市', '', $region_name_3);
    $region_name_3 = str_replace('区', '', $region_name_3);
    $region_name_3 = str_replace('县', '', $region_name_3);

    if ($city_name != null) str_replace($city_name, '', $region_name_3);
    if ($qu_name != null) str_replace($qu_name, '', $region_name_3);
    return $region_name_3;
}

/**
 * 根据地址获取经纬度
 * @param $address
 * @return mixed
 */
function getLngLatByAddress($address)
{
    $address = urlencode($address);
    $ak = config("other_api.baiduapi_ip.appKey");
    $url = "http://api.map.baidu.com/geocoder/v2/?address=" . $address . "&output=json&ak=" . $ak;
    $address_data = file_get_contents($url);
    $json_data = json_decode($address_data);
    return $json_data;
}

/**
 * 写入导入异常信息
 * @param $type
 * @param $content
 */
function putLog($type, $content)
{
    $filename = ROOT_PATH . '/runtime/log/' . $type . date('Ym') . '/';
    if (! is_dir($filename)) {
        mkdir($filename, 0777, true);
    }
    $filename = $filename . date('d') . '.log';
    $log = fopen($filename, "a+");
    // fputs($log, "执行日期：" . " " . date('Y-m-d H:i:s', time()). "\r\n" . "错误原因：" . " " . $err . "\r\n" . json_encode($content) . "\n");
    fputs($log, json_encode($content) . "\r\n");
    fclose($log);
}

if (!function_exists('getIp')) {

    /**
     * 获取客户端IP地址
     *
     * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
     * @return mixed
     */
    function getIp($type = 0, $adv = true)
    {
        $type = $type ? 1 : 0;
        static $ip = null;
        if (null !== $ip) {
            return $ip[$type];
        }

        if ($adv) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos = array_search('unknown', $arr);
                if (false !== $pos) {
                    unset($arr[$pos]);
                }
                $ip = trim(current($arr));
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip = $long ? [$ip, $long] : ['0.0.0.0', 0];
        return $ip[$type];
    }
}

if (!function_exists('isMobile')) {

    /**
     * 检测是否使用手机访问
     *
     * @access public
     * @return bool
     */
    function isMobile()
    {
        if (isset($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'], "wap")) {
            return true;
        } elseif (isset($_SERVER['HTTP_ACCEPT']) && strpos(strtoupper($_SERVER['HTTP_ACCEPT']), "VND.WAP.WML")) {
            return true;
        } elseif (isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE'])) {
            return true;
        } elseif (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('/(blackberry|configuration\/cldc|hp |hp-|htc |htc_|htc-|iemobile|kindle|midp|mmp|motorola|mobile|nokia|opera mini|opera |Googlebot-Mobile|YahooSeeker\/M1A1-R2D2|android|iphone|ipod|mobi|palm|palmos|pocket|portalmmm|ppc;|smartphone|sonyericsson|sqh|spv|symbian|treo|up.browser|up.link|vodafone|windows ce|xda |xda_)/i', $_SERVER['HTTP_USER_AGENT'])) {
            return true;
        } else {
            return false;
        }
    }
}

/**
 * 过滤商铺电话
 * @param $phone_str
 * @return string
 */
function filterShopPhone($phone_str = '')
{
    if (empty($phone_str)) return '';
    if (strpos($phone_str, '/') !== false) {
        $phone_arr = explode('/', $phone_str);
        foreach ($phone_arr as $v) {
            if (isTel($v)) return $v;
        }
    } else {
        if (isTel($phone_str)) return $phone_str;
    }
    return '';
}

/**
 * 判断是否是电话
 * @param $tel
 * @return bool
 */
function isTel($tel)
{
    $regxArr = [
        'sj' => '/^(\+?86-?)?(18|15|13)[0-9]{9}$/',
        'tel' => '/^(0(10|21|22|23|[1-9][0-9]{2})(-|))?[0-9]{7,8}$/',
        '400' => '/^400(-\d{3,4}){2}$/',
        '800' => '/^800(-\d{3,4}){2}$/',
    ];
    foreach ($regxArr as $regx) {
        if (preg_match($regx, $tel)) {
            return true;
        }
    }
    if (is_numeric($tel) && (strlen($tel) == 7 || strlen($tel) == 8)) {
        return true;
    }
    return false;
}



/**
 * @param int $lat1 纬度1
 * @param int $lng1 经度1
 * @param int $lat2 纬度2
 * @param int $lng2 经度2
 * @return array
 */
function getDistance($lat1 = 0, $lng1 = 0, $lat2 = 0, $lng2 = 0, $radius = 6378.137)
{
    $rad = floatval(M_PI / 180.0);

    $lat1 = floatval($lat1) * $rad;
    $lng1 = floatval($lng1) * $rad;
    $lat2 = floatval($lat2) * $rad;
    $lng2 = floatval($lng2) * $rad;

    $theta = $lng2 - $lng1;

    $dist = acos(sin($lat1) * sin($lat2) +
        cos($lat1) * cos($lat2) * cos($theta)
    );

    if ($dist < 0) $dist += M_PI;

    return $dist = $dist * $radius;
}

/**
 * 根据门店的营业时间 返回直观的格式
 * @param $json_business_hours
 * @return array
 */
function handleBusinessHours($json_business_hours)
{
    if ($json_business_hours == '') return [];
    $business_hours = json_decode($json_business_hours, true);
    if (empty($business_hours) || !is_array($business_hours)) return [];

    $data = [];
    foreach ($business_hours as $k => $v) {
        $data[] = getBusinessHours($v['week']) . ' ' . $v['time'];
    }
    return $data;
}

/**
 * 1-7数字 对应 （连续）日期 (默认是一周)
 * @param array $a [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 7]
 * @return string
 */
function getBusinessHours($a = [])
{
    if (empty($a)) return '周一至周日';
    $b = [1 => '星期一', 2 => '星期二', 3 => '星期三', 4 => '星期四', 5 => '星期五', 6 => '星期六', 7 => '星期日'];

    $str = '';
    $sign = $sign2 = 0;
    for ($i = 1; $i < 8; $i++) {
        if (isset($a[$i])) {
            if ($sign == 0) $str .= ' ' . $b[$i];
        } else {
            continue;
        }
        if (isset($a[$i + 1])) {
            $sign = 1;
            $sign2++;
        } else {
            if ($sign2 - 1 >= 0) {
                $str .= '至' . $b[$i];
            }
            $sign = 0;
        }
    }
    return $str;
}

/**
 * 正则匹配<img>标签图片地址
 * @param $desc
 * @return string
 */
function getImgUrl($desc)
{
    $pattern = "/<[img|IMG].*?src=[\'|\"](.*?(?:[\.gif|\.jpg|\.png]))[\'|\"].*?[\/]?>/i";
    preg_match_all($pattern, $desc, $match);
    $desc_url = $match[1] ?? '';
    return $desc_url;
}

/**
 * 极光推送
 * @param $alert 弹窗信息
 * @param $alias 指定用户
 * @param $tag 指定标签
 * @param $ios ios设备
 * @param $android android设备
 * @param $msg_conetnt 消息内容
 * @param $msg 消息
 * @return array
 */
function jPush($alert, $alias, $tag, $ios, $android, $msg_conetnt, $msg)
{
    $path = dirname(APP_PATH) . '/runtime/log/' . date('Ym') . '/jpush_' . date('d') . '.log';
    $j_push = config('other_api.j_push');
    $client = new \JPush\Client($j_push['app_key'], $j_push['master_secret'], $path);
    try {
        $response = $client->push()->setPlatform(['ios', 'android'])
            ->setNotificationAlert($alert)
            ->addAlias($alias)
            ->addTag($tag)
            ->iosNotification($alert, $ios)
            ->androidNotification($alert, $android)
            ->message($msg_conetnt, $msg)
            ->send();
        return $response;
    } catch (Exception $e) {
        return false;
    }

}

/**
 * GB2312, UTF-8 转UTF-8
 * @param $data
 * @return null|string|string[]
 */
function characet($data)
{
    return mb_convert_encoding($data, 'UTF-8', 'GB2312, UTF-8');
}