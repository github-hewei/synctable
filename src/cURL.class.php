<?php // CODE BY HW 
//对 cURL 类的封装
//版本: 1.0
//时间: 2020年3月23日
//作者: hewei
class cURL {
    public $url;        //链接
    public $reqData;    //请求参数
    public $isPost;     //是否POST
    public $headers;    //请求头
    public $options;    //cURL选项
    public $resInfo;    //响应信息
    public $resContent; //响应内容
    public $cookieJar;  //Cookie文件
    public $files;      //发送文件

    /**
     * 参数初始化
     */
    private function _init() {
        $this->isPost       = false;
        $this->url          = '';
        $this->reqData      = '';
        $this->cookieJar    = '';
        $this->headers      = array();
        $this->options      = array();
        $this->files        = array();
        $this->resInfo      = array();
        $this->resContent   = '';
        $this->addOption(CURLOPT_RETURNTRANSFER, 1);
        $this->addOption(CURLOPT_SSL_VERIFYPEER, 0);
        $this->addOption(CURLOPT_SSL_VERIFYHOST, 0);
    }

    /**
     * 构造函数
     */
    public function __construct() {
        $this->_init();
    }

    /**
     * 添加单个或多个cURL选项
     * @param int|array $option 选项名或多个选项的数组
     * @param mixed $value 单个选项的值
     */
    public function addOption($option, $value = 0) {
        if(is_int($option) || is_numeric($option) || is_string($option)) {
            $this->options[$option] = $value;
        } elseif(is_array($option)) {
            foreach($option as $key => $val) {
                $this->option[$key] = $val;
            }
        }
        return $this;
    }

    /**
     * 获取单个或全部cURL选项
     * @param string|null $name 选项的键或NULL
     * @return mixed 选项的值或全部选项数组
     */
    public function getOption($name = null) {
        if(!is_null($name)) {
            if(!isset($this->options[$name])) {
                throw new cURLException('Not found the option: ' . $name);
            }
            return $this->options[$name];
        }
        return $this->options;
    }

    /**
     * 添加单个或多个Http请求头
     * @param string|array $arg 单个请求头信息或多个信息的数组
     */
    public function addHeader($arg) {
        if(is_string($arg)) {
            array_push($this->headers, $arg);
        } elseif(is_array($arg)) {
            $this->headers = array_merge($this->headers, $arg);
        }
        return $this;
    }

    /**
     * 获取Http请求头
     * @return array 包含请求头的数组
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * 设置请求地址
     * @param string $arg 要请求的链接
     */
    public function setUrl($arg) {
        $this->url = $arg;
        return $this;
    }

    /**
     * 获取请求地址
     * @return string 要请求的链接
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * 设置请求参数
     * 数组: multipart/form-data, 字符串: application/x-www-form-urlencoded
     * @param string|array $arg 请求GET参数或POST参数
     */
    public function setData($arg) {
        $this->reqData = $arg;
        return $this;
    }

    /**
     * 获取请求参数
     * @return mixed 请求GET参数或POST参数
     */
    public function getData() {
        return $this->reqData;
    }

    /**
     * 设置存储Cookie的文件或设置使用默认文件
     * @param boolean|string $arg 存储Cookie的文件名或true
     */
    public function setCookie($arg = true) {
        if((is_bool($arg) || is_int($arg)) && $arg) {
            $this->cookieJar = sys_get_temp_dir() . '/cookie_' . uniqid() . '.tmp';
        } elseif(is_string($arg)) {
            $this->cookieJar = $arg;
        }
        if(!is_dir(dirname($this->cookieJar)) && !mkdir(dirname($this->cookieJar), 0777, true)) {
            throw new cURLException('Failed to create directory: ' . dirname($this->cookieJar));
        }
        return $this;
    }

    /**
     * 获取存储Cookie的文件
     * @return string 存储Cookie的文件名
     */
    public function getCookie() {
        return $this->cookieJar;
    }

    /**
     * 设置自定义ua或使用默认ua
     * @param boolean|string $arg 自定义UA或true
     */
    public function setUa($arg = true) {
        if((is_bool($arg) || is_int($arg)) && $arg) {
            $this->addOption(CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:74.0) Gecko/20100101 Firefox/74.0');
        } elseif(is_string($arg)) {
            $this->addOption(CURLOPT_USERAGENT, $arg);
        }
        return $this;
    }

    /**
     * 添加单个或多个传输文件
     * @param array|string $file 文件路径或包含多个文件参数的数组
     * @param string $name 单个文件的键名 默认为 file
     * @throws cURLException 文件不存在或 CURLFile 类不存在时抛出异常
     */
    public function addFile($file, $name = 'file') {
        if(!class_exists('CURLFile')) {
            throw new cURLException('Class CURLFile is undefined');
        }
        if(is_string($file)) {
            if(!file_exists($file)) {
                throw new cURLException('File not found: ' . $file);
            }
            $this->files[$name] = $file;
        } elseif(is_array($file)) {
            foreach($file as $key => $value) {
                if(!file_exists($value)) {
                    throw new cURLException('File not found: ' . $value);
                }
                $this->files[$key] = $value;
            }
        }
        return $this;
    }

    /**
     * 获取全部传输文件
     * @return array 要传输的文件信息数组
     */
    public function getFiles() {
        return $this->files;
    }

    /**
     * 设置请求方式是否为POST
     * @param boolean $arg true为POST 反之为 GET
     */
    public function setPost($arg = true) {
        $this->isPost = $arg ? true : false;
        return $this;
    }

    /**
     * 获取当前设置的请求方式
     * @return string 请求方式 POST 或 GET
     */
    public function getMethod() {
        return $this->isPost ? 'POST' : 'GET';
    }

    /**
     * 添加Http请求头模拟ajax请求
     */
    public function ajax() {
        $this->addHeader('X-Requested-With: XMLHttpRequest');
        return $this;
    }

    /**
     * 发送网络请求
     * @throws cURLException 选项错误或cURL返回错误时抛出异常
     */
    public function send() {
        if( count($this->headers) > 0 ) {
            $this->addOption(CURLOPT_HTTPHEADER, $this->headers);
        }
        if( strlen($this->cookieJar) > 0 ) {
            $this->addOption(CURLOPT_COOKIEJAR, $this->cookieJar);
            $this->addOption(CURLOPT_COOKIEFILE, $this->cookieJar);
        }
        if( count($this->files) > 0 ) {
            foreach($this->files as $key => $file) {
                $cf = new CURLFile($file);
                $this->setPost(true);
                if(is_array($this->reqData)) {
                    $this->reqData[$key] = $cf;
                }
            }
        }
        $reqUrl = $this->url;
        if($this->isPost) {
            $this->addOption(CURLOPT_POST, 1);
            $this->addOption(CURLOPT_POSTFIELDS, $this->reqData);
        } else {
            if(is_array($this->reqData)) {
                $reqUrl .= '?' . http_build_query($this->reqData);
            } elseif(is_string($this->reqData)) {
                $reqUrl .= '?' . ltrim(trim($this->reqData), '?');
            }
        }
        $this->addOption(CURLOPT_URL, $reqUrl);
        $ch = curl_init();
        if(false == curl_setopt_array($ch, $this->options)) {
            throw new cURLException('The options has error');
        }
        $this->resContent = curl_exec($ch);
        $this->resInfo = curl_getinfo($ch);
        if(CURLE_OK !== curl_errno($ch)) {
            $error = 'CURL_ERR: ' . curl_error($ch);
            if(function_exists('curl_strerror')) {
                $error .= '; CURL_STR_ERR: ' . curl_strerror(curl_errno($ch));
            }
            throw new cURLException($error);
        }
        curl_close($ch);
        return $this;
    }

    /**
     * 获取响应内容
     * @return string 目标地址响应信息
     */
    public function getContent() {
        return $this->resContent;
    }

    /**
     * 获取单个获全部响应头信息
     * @param null|string $name 信息的键值或null
     * @return mixed 响应头信息
     * @throws cURLException 键值不存在时抛出异常
     */
    public function getInfo($name = null) {
        if(!is_null($name)) {
            if(!isset($this->resInfo[$name])) {
                throw new cURLException('Not found the info: ' . $name);
            }
            return $this->resInfo[$name];
        }
        return $this->resInfo;
    }

    /**
     * 清理重置所有参数
     */
    public function clear() {
        $this->_init();
        return $this;
    }

}

//cURL异常类
class cURLException extends Exception {}


if( $_SERVER['PHP_SELF'] === 'cURL.class.php' ) {
    //用法示例伪代码
    try {
        ini_set('display_errors', 'On');
        $curl = new cURL;
        $curl->setUrl('http://xxxx.com/temp.php?get=ok');
        $curl->setPost(1);
        $curl->setData(array('post' => 'ok'));
        $curl->ajax();
        $curl->setCookie('d:/temp/cookie.tmp');
        $curl->addFile('d:/temp/temp.php', 'code');
        $curl->addHeader('sign: ' . md5());
        $curl->addOption(CURLOPT_HEADER, 1);
        $curl->send();
        echo $curl->getContent();
        echo $curl->getInfo('content_type');
        $curl->clear();
        exit("OK\n");

    } catch (cURLException $e) {
        echo "[ " . $e->getMessage() . " ]\n";
        throw $e;
    } catch(Exception $e) {
        throw $e;
    }
}
