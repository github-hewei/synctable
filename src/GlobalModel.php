<?php // CODE BY HW
//数据操作

class GlobalModel {

    //获取当前数据库的所有数据表
    public static function getTables() {
        $sql = "SELECT TABLE_NAME,TABLE_ROWS FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . DB_NAME . "'";
        $rows = db::instance()->query($sql)->fetchAll();
        return $rows;
    }

    //导出数据表
    public static function exportTables($tables) {
        $filename = dirname(__FILE__) . '/../tmp/' . uniqid() . '.tmp';
        create_path(dirname($filename));
        $handle = fopen($filename, 'w+');
        flock($handle, LOCK_EX);
        fwrite($handle, encrypt("-- SYNCTABLE --") . "\n");
        fwrite($handle, encrypt("SET NAMES UTF8;") . "\n");
        foreach($tables as $table) {
            $sql = sprintf("SHOW CREATE TABLE `%s`;", $table);
            $row = db::instance()->query($sql)->fetch(PDO::FETCH_NUM);
            $createSql = array_filter(array_map("trim", explode("\n", $row[1])));
            fwrite($handle, encrypt(sprintf("DROP TABLE IF EXISTS `%s`;", $row[0])) . "\n");
            fwrite($handle, encrypt(implode(" ", $createSql) . ";") . "\n");
            fwrite($handle, encrypt(sprintf("LOCK TABLES `%s` WRITE;", $row[0])) . "\n");
            //查询数据
            $sql = sprintf("SELECT * FROM `%s`;", $row[0]);
            $rows = db::instance()->query($sql)->fetchAll();
            $tmpArr = [];
            $baseSql = sprintf("INSERT INTO `%s` VALUES ", $table);
            foreach($rows as $key => $value) {
                $line = [];
                foreach($value as $item) {
                    $line[] = db::instance()->quote($item);
                }
                $tmpArr[] = sprintf("(%s)", implode(",", $line));
                if(($key + 1) % 2000 === 0) {
                    $sql = $baseSql . sprintf("%s;", implode(",", $tmpArr));
                    fwrite($handle, encrypt($sql) . "\n");
                    $tmpArr = [];
                }
            }
            if(count($tmpArr)) {
                $sql = $baseSql . sprintf("%s;", implode(",", $tmpArr));
                fwrite($handle, encrypt($sql) . "\n");
                unset($tmpArr);
            }
            fwrite($handle, encrypt("UNLOCK TABLES;") . "\n");
        }
        flock($handle, LOCK_UN);
        fclose($handle);
        return $filename;
    }

    //导入数据表
    public static function importTables($filename) {
        $handle = fopen($filename, 'r');
        $num = 0;
        while(!feof($handle)) {
            $line = trim(fgets($handle));
            if(!$line) {
                continue ;
            }
            $row = decrypt($line);
            if(!$row) {
                continue ;
            }
            if(substr($row, 0, 2) === '--') {
                continue ;
            }
            db::instance()->exec($row);
            $num++;
        }
        return $num;
    }

}
