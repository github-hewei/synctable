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
            self::$instance->exec('set names utf8');
        }
        return self::$instance;
    }
}
