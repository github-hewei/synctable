<?php // CODE BY HW
require_once dirname(__FILE__) . '/src/common.php';

$filename = dirname(__FILE__) . "/tmp/5ff4500ab4ffd/5ff45009a48ed.tmp";

$handle = fopen($filename, 'r');
while(!feof($handle)) {
    $line = trim(fgets($handle));
    if($line) {
        echo decrypt($line) . "\n\n";
    }
}
fclose($handle);


