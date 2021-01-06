<?php // CODE BY HW
//数据库操作
class db extends \PDO {
    public static $instance;
    public static function instance() {
        if(!self::$instance) {
            $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME;
            self::$instance = new self($dsn, DB_USER, DB_PASS);
            self::$instance->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$instance->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            //是否使用本地模拟预处理，0为关闭模拟，使用MySQL的预处理
            //关闭此选项后，查询出来的数据，处理NULL之外都是string类型
            self::$instance->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);
            self::$instance->exec('set names utf8');
        }
        return self::$instance;
    }
}
