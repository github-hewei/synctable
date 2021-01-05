<?php // CODE BY HW
//AES加密

class AESx {

    public static $instance;

    public $method = 'AES-128-ECB';
    public $password = '';

    public function __construct() {
        if(defined('AES_PASSWORD')) {
            $this->password = AES_PASSWORD;
        }
    }

    public static function instance() {
        if(!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    //加密
    public static function Enc($data) {
        $encrypt = openssl_encrypt($data, self::instance()->method, self::instance()->password, OPENSSL_RAW_DATA);
        return base64_encode($encrypt);
    }

    //解密
    public static function Dec($data) {
        $decrypt = openssl_decrypt(base64_decode($data), self::instance()->method, self::instance()->password, OPENSSL_RAW_DATA);
        return $decrypt;
    }

}
