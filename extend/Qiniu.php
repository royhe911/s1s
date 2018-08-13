<?php

// ------------------------
// 七牛上传和token生成
// -------------------------
class Qiniu
{

    /**
     * 默认上传配置
     */
    private $config = [
        'mimes' => [], // 允许上传的文件 mime 类型
        'max_size' => 0, // 上传的文件大小限制 (0-不做限制)
        'exts' => [], // 允许上传的文件后缀
        'url' => "http://upload.qiniu.com/", // 上传的地址
        'param' => []
    ];
    
    // 参数
    private $error = '';
    // 上传错误信息
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
     *
     * @param array $config 配置
     * @param string $driver 要使用的上传驱动 LOCAL-本地上传驱动，FTP-FTP上传驱动
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
        /* 获取配置 */
        $this->config = array_merge($this->config, $config);
        
        /* 调整配置，把字符串配置参数转换为数组 */
        if (! empty($this->config['mimes'])) {
            if (is_string($this->config['mimes'])) {
                $this->config['mimes'] = explode(',', $this->config['mimes']);
            }
            $this->config['mimes'] = array_map('strtolower', $this->config['mimes']);
        }
        if (! empty($this->config['exts'])) {
            if (is_string($this->config['mimes'])) {
                $this->config['exts'] = explode(',', $this->config['mimes']);
            }
            $this->config['exts'] = array_map('strtolower', $this->config['mimes']);
        }
        return $this;
    }

    private static $instance_manager;
    // \Qiniu\Storage\UploadManager的单例
    
    /**
     * 单例化\Qiniu\Storage\UploadManager
     *
     * @return \Qiniu\Storage\UploadManager
     */
    public static function manager()
    {
        if (self::$instance_manager === null) {
            self::$instance_manager = new \Qiniu\Storage\UploadManager();
        }
        return self::$instance_manager;
    }

    /**
     * 生成token
     *
     * @param int $expires
     * @param null $bucket
     * @param null $key
     * @param null $policy
     * @param bool $strictPolicy
     * @return string
     */
    public static function getUploadToken($expires = 3600, $bucket = null, $key = null, $policy = null, $strictPolicy = true)
    {
        if (empty($bucket))
            $bucket = config("qiniu.bucket");
            // 初始化签权对象
        $auth = new \Qiniu\Auth(config("qiniu.accessKey"), config("qiniu.secretKey"));

        $policy['isPrefixalScope'] = 1;
        $token = $auth->uploadToken($bucket, $key, $expires, $policy, $strictPolicy);
        return $token;
    }

    /**
     * 下载私有资源
     *
     * @param string $baseUrl
     * @param number $expires
     */
    public function privateDownloadUrl($baseUrl, $expires = 3600)
    {
        $auth = new \Qiniu\Auth(config("qiniu.accessKey"), config("qiniu.secretKey"));
        return $auth->privateDownloadUrl($baseUrl);
        
        // $token = $this->token($expires, config("qiniu.private_bucket"));
        // return "$baseUrl&token=$token";
    }

    /**
     * 在线解压
     */
    public function unzip($key, $prefix, $notify_url)
    {
        // 去我们的portal 后台来获取AK, SK
        $auth = new \Qiniu\Auth(config("qiniu.accessKey"), config("qiniu.secretKey"));
        
        $pfop = new Qiniu\Processing\PersistentFop($auth, config("qiniu.private_bucket"), null, $notify_url);
        
        $fops = "unzip_service/bucket/" . \Qiniu\base64_urlSafeEncode(config("qiniu.preview_bucket"));
        
        $fops .= "/prefix/" . \Qiniu\base64_urlSafeEncode($prefix);
        $fops .= "/overwrite/1";
        
        return $pfop->execute_wait($key, $fops);
        
        /*
         * unzip/bucket/<UrlsafeBase64EncodedBucket>/prefix/<UrlsafeBase64EncodedP
         * refix>/overwrite/<1 or 0>;
         */
        // list ($id, $err) = $pfop->execute($key, $fops);
        // // echo "\n====> pfop mkzip result: \n";
        // if ($err != null) {
        // return $err;
        // // var_dump($err);
        // } else {
        // return $id;
        // // echo "PersistentFop Id: $id\n";
        // // $res = "http://api.qiniu.com/status/get/prefop?id=$id";
        // // echo "Processing result: $res";
        // }
    }

    /**
     * 在线解压 共有
     */
    public function unzip_pub($key, $prefix, $notify_url)
    {
        // 去我们的portal 后台来获取AK, SK
        $auth = new \Qiniu\Auth(config("qiniu.accessKey"), config("qiniu.secretKey"));
        
        $pfop = new Qiniu\Processing\PersistentFop($auth, config("qiniu.bucket"), null, $notify_url);
        
        $fops = "unzip_service/bucket/" . \Qiniu\base64_urlSafeEncode(config("qiniu.bucket"));
        
        $fops .= "/prefix/" . \Qiniu\base64_urlSafeEncode($prefix);
        $fops .= "/overwrite/1";
        
        return $pfop->execute_wait($key, $fops);
    }

    /**
     * 将资源从一个空间到另一个空间
     *
     * @param $from_bucket 待操作资源所在空间
     * @param $from_key 待操作资源文件名
     * @param $to_bucket 目标资源空间名
     * @param $to_key 目标资源文件名
     *
     * @return mixed 成功返回NULL，失败返回对象Qiniu\Http\Error
     * @link http://developer.qiniu.com/docs/v6/api/reference/rs/move.html
     */
    public function move($from_bucket, $from_key, $to_bucket, $to_key)
    {
        $auth = new \Qiniu\Auth(config("qiniu.accessKey"), config("qiniu.secretKey"));
        $qiniu_bucket = new \Qiniu\Storage\BucketManager($auth);
        return $qiniu_bucket->move($from_bucket, $from_key, $to_bucket, $to_key);
    }

    /**
     * 删除单个资源
     *
     * @param $from_bucket
     * @param $key
     * @return mixed
     */
    public function deleteOne($from_bucket, $key)
    {
        $auth = new \Qiniu\Auth(config("qiniu.accessKey"), config("qiniu.secretKey"));
        $qiniu_bucket = new \Qiniu\Storage\BucketManager($auth);
        return $qiniu_bucket->delete($from_bucket, $key);
    }

    /**
     * 获取资源的元信息，但不返回文件内容
     *
     * @param $bucket 待获取信息资源所在的空间
     * @param $key 待获取资源的文件名
     *
     * @return array 包含文件信息的数组，类似：
     *         [
     *         "hash" => "<Hash string>",
     *         "key" => "<Key string>",
     *         "fsize" => "<file size>",
     *         "putTime" => "<file modify time>"
     *         ]
     *        
     * @link http://developer.qiniu.com/docs/v6/api/reference/rs/stat.html
     */
    public function stat($bucket, $key)
    {
        $auth = new \Qiniu\Auth(config("qiniu.accessKey"), config("qiniu.secretKey"));
        $qiniu_bucket = new \Qiniu\Storage\BucketManager($auth);
        return $qiniu_bucket->stat($bucket, $key);
    }

    public function fetchFile($url, $bucket, $key)
    {
        if (empty($bucket))
            $bucket = config("qiniu.bucket");
        
        $auth = new \Qiniu\Auth(config("qiniu.accessKey"), config("qiniu.secretKey"));
        $qiniu_bucket = new \Qiniu\Storage\BucketManager($auth);
        
        list ($ret, $err) = $qiniu_bucket->fetch($url, $bucket, $key);
        return $err;
    }
    /**
     * 上传base64图片
     *
     * @param unknown $data
     * @param string $prefix
     * @param unknown $name
     * @param unknown $token
     * @param unknown $params
     * @param string $mime
     * @param string $checkCrc
     */
    public function uploadBinary($data, $prefix = "", $name = null, $token = null, $params = null, $mime = 'application/octet-stream', $checkCrc = false)
    {
        if (empty($name)) {
            $ext = ".jpg";
            if (strstr($mime, "image/bmp")) {
                $ext = ".bmp";
            } elseif (strstr($mime, "image/png")) {
                $ext = ".png";
            } elseif (strstr($mime, "image/jpeg")) {
                $ext = ".jpg";
            } elseif (strstr($mime, "image/gif")) {
                $ext = ".gif";
            }
            $name = $prefix . base_convert(time() * 1000, 10, 36) . "_" . base_convert(microtime(), 10, 36) . uniqid() . $ext;
        }
        if (empty($token))
            $token = self::getUploadToken();
        if (empty($mime))
            $mime = 'application/octet-stream';
        
        list ($ret, $error) = self::manager()->put($token, $name, $data, $params, $mime, $checkCrc);
        
        if ($error !== null) {
            $this->error = $error->message();
            return false;
        } else {
            return $ret;
        }
    }

    /**
     * 上传单个文件，文件直传
     *
     * @param $file_path
     * @param string $prefix 文件名前缀，可以模拟目录
     * @param null $name
     * @param null $token
     * @param null $params
     * @param string $mime
     * @param bool $checkCrc
     * @return bool
     */
    public function uploadOne($file_path, $prefix = "", $name = null, $token = null, $params = null, $mime = 'application/octet-stream', $checkCrc = false)
    {
        if (empty($name))
            $name = $this->get_random($prefix) . "." . pathinfo($file_path, PATHINFO_EXTENSION);
        if (empty($token))
            $token = self::getUploadToken();
        if (empty($mime))
            $mime = 'application/octet-stream';
        $this->error = null;
        
        list ($ret, $error) = self::manager()->putFile($token, $name, $file_path, $params, $mime, $checkCrc);
        
        if ($error !== null) {
            $this->error = $error->message();
            return false;
        } else {
            return $ret;
        }
    }

    /**
     * 上传文件，包含$_FILES的上传
     * 返回数据包含上传信息部分如果是字符串表示错误信息，如果是数组表示成功
     *
     * @param string $prefix
     * @param null $params
     * @param bool $checkCrc
     * @return array|bool
     */
    public function upload($prefix = "", $params = null, $checkCrc = false)
    {
        if (! isset($_FILES) || empty($_FILES)) {
            $this->error = '没有上传的文件！';
            return false;
        }
        
        // 逐个检测并上传文件
        $info = []; // 文件上传信息数组
        foreach ($_FILES as $key => $files) {
            foreach ($this->reArrayFiles($files) as $file) {
                // 文件上传检测
                if (! $this->check($file)) {
                    $info[$key][] = $this->error;
                    continue;
                }
                // 文件名生成
                if (isset($file['name'])) {
                    $name = $this->get_random($prefix) . "." . pathinfo($file['name'], PATHINFO_EXTENSION);
                } else {
                    $name = $this->get_random($prefix);
                }
                
                // 文件上传
                $ret = $this->uploadOne($file['tmp_name'], $prefix, $name, null, $params, isset($file['type']) ? $file['type'] : null, $checkCrc);
                if ($ret) {
                    $info[$key][] = array_merge($ret, $file);
                } else {
                    $info[$key][] = $this->error;
                }
            }
        }
        
        // 简化返回数据格式
        return count($_FILES) == 1 ? end($info) : $info;
    }

    /**
     * 对文件信息进行处理
     *
     * @param $file_post
     * @return array
     */
    private function reArrayFiles($file_post)
    {
        $file_ary = [];
        if (is_array($file_post['tmp_name'])) {
            $file_count = count($file_post['tmp_name']);
            $file_keys = array_keys($file_post);
            for ($i = 0; $i < $file_count; $i ++) {
                foreach ($file_keys as $key) {
                    $file_ary[$i][$key] = $file_post[$key][$i];
                }
            }
        } else {
            $file_ary[] = $file_post;
        }
        return $file_ary;
    }

    /**
     * 获取最后一次上传错误信息
     *
     * @return string 错误信息
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 检查上传的文件
     *
     * @param array $file 文件信息
     */
    private function check($file)
    {
        if (! isset($file['tmp_name']) || ! file_exists($file['tmp_name'])) {
            $this->error = '上传的文件不存在';
            return false;
        }
        
        // 文件上传失败，捕获错误代码
        if (isset($file['error']) && $file['error']) {
            $this->error($file['error']);
            return false;
        }
        
        // 检查文件大小
        if (isset($file['size']) && ! $this->checkSize($file['size'])) {
            $this->error = '上传文件大小不符！';
            return false;
        }
        
        // 检查文件Mime类型
        if (isset($file['type']) && $file['type']) {
            if (! $this->checkMime($file['type'])) {
                $this->error = '上传文件MIME类型不允许！';
                return false;
            }
        }
        
        // 检查文件后缀
        if (isset($file['ext']) && ! $this->checkExt($file['ext'])) {
            $this->error = '上传文件后缀不允许';
            return false;
        }
        
        // 通过检测
        return true;
    }

    /**
     * 获取错误代码信息
     *
     * @param string $errorNo 错误号
     */
    private function error($errorNo)
    {
        switch ($errorNo) {
            case 1:
                $this->error = '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值！';
                break;
            case 2:
                $this->error = '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值！';
                break;
            case 3:
                $this->error = '文件只有部分被上传！';
                break;
            case 4:
                $this->error = '没有文件被上传！';
                break;
            case 6:
                $this->error = '找不到临时文件夹！';
                break;
            case 7:
                $this->error = '文件写入失败！';
                break;
            default:
                $this->error = '未知上传错误！';
        }
    }

    /**
     * 检查文件大小是否合法
     *
     * @param integer $size 数据
     */
    private function checkSize($size)
    {
        return ! ($size > $this->config['max_size']) || (0 == $this->config['max_size']);
    }

    /**
     * 检查上传的文件MIME类型是否合法
     *
     * @param string $mime 数据
     */
    private function checkMime($mime)
    {
        return empty($this->config['mimes']) ? true : in_array(strtolower($mime), $this->config['mimes']);
    }

    /**
     * 检查上传的文件后缀是否合法
     *
     * @param string $ext 后缀
     */
    private function checkExt($ext)
    {
        return empty($this->config['exts']) ? true : in_array(strtolower($ext), $this->config['exts']);
    }

    /**
     * 生成随机字符串
     *
     * @param string $prefix
     * @return string
     */
    private function get_random($prefix = '')
    {
        return $prefix . base_convert(time() * 1000, 10, 36) . "_" . base_convert(microtime(), 10, 36) . uniqid();
    }

    /**
     * 生成token
     *
     * @param int $expires
     * @param null $bucket
     * @param null $key
     * @param null $policy
     * @param bool $strictPolicy
     * @return string
     */
    public static function token($expires = 3600, $bucket = null, $key = null, $policy = null, $strictPolicy = true)
    {
        if (empty($bucket))
            $bucket = config("qiniu.bucket");
            // 初始化签权对象
            $auth = new \Qiniu\Auth(config("qiniu.accessKey"), config("qiniu.secretKey"));
            $token = $auth->uploadToken($bucket, $key, $expires, $policy, $strictPolicy);
            return $token;
    }
}
