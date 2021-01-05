<?php // CODE BY HW
//公共文件
ini_set('date.timezone', 'Asia/Shanghai');
ini_set('max_execution_time', '0');
ini_set('display_errors', 'On');
ini_set('memory_limit', '1024M');
session_start();

require_once dirname(__FILE__) . '/../const.php';
require_once dirname(__FILE__) . '/db.php';
require_once dirname(__FILE__) . '/cURL.class.php';
require_once dirname(__FILE__) . '/rsa.php';
require_once dirname(__FILE__) . '/aes.php';
require_once dirname(__FILE__) . '/GlobalModel.php';

define('LOCAL_URL', $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . substr($_SERVER['DOCUMENT_URI'], 0, strrpos($_SERVER['DOCUMENT_URI'], '/')));

if(!function_exists('json')) {
    function json($arg) {
        return json_encode($arg, JSON_UNESCAPED_UNICODE);
    }
}
if(!function_exists('get')) {
    function get($name, $def = '') {
        return isset($_GET[$name]) ? $_GET[$name] : $def;
    }
}
if(!function_exists('post')) {
    function post($name, $def = '') {
        return isset($_POST[$name]) ? $_POST[$name] : $def;
    }
}
if(!function_exists('create_path')) {
    function create_path($path) {
        return (!is_dir($path) && !mkdir($path, 0755, true)) ? false : true;
    }
}
if(!function_exists('LogX')) {
    function LogX($msg) {
        $filename = dirname(__FILE__) . '/../log/' . date('Ymd') . '.log';
        if(create_path(dirname($filename))) {
            $handle = fopen($filename, 'a+');
            fwrite($handle, sprintf("[%s]: %s\n", date('Y-m-d H:i:s'), $msg));
            fclose($handle);
        }
    }
}
if(!function_exists('encrypt')) {
    function encrypt($data, $method = 'AESx') {
        return ($method === 'AESx') ? AESx::Enc($data) : RSAx::Enc($data);
    }
}
if(!function_exists('decrypt')) {
    function decrypt($data, $method = 'AESx') {
        return ($method === 'AESx') ? AESx::Dec($data) : RSAx::Dec($data);
    }
}
if(!function_exists('array_column')) {
    /**
     * 简单模拟一下 array_column 函数，因为php5.5以下版本没有此函数
     */
    function array_column($input, $column) {
        $arr = [];
        foreach($input as $value) {
            if(isset($value[$column])) {
                $arr[] = $value[$column];
            }
        }
        return $arr;
    }
}
