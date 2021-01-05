<?php // CODE BY HW
require_once dirname(__FILE__) . '/rsa.class.php';

class RSAx extends RSA{
    public static $instance ;
    
    public static function instance() {
        if(!self::$instance) {
            $publicKey = file_get_contents(dirname(__FILE__) . '/rsa_public_key.php');
            $privateKey = file_get_contents(dirname(__FILE__) . '/rsa_private_key.php');
            self::$instance = new self(substr($privateKey, 14), substr($publicKey, 14));
        }
        return self::$instance;
    }

    //加密
    public static function Enc($src, $type = 'public') {
        return self::instance()->Encrypt($src, $type);
    }

    //解密
    public static function Dec($src, $type = 'private') {
        return self::instance()->Decrypt($src, $type);
    }

}

//RSAx::Enc('xxxx');
//RSAx::Dec('xxxx');
