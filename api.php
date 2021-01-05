<?php // CODE BY HW
//接口入口
require_once dirname(__FILE__) . '/src/common.php';
$data = post('data');
if(empty($data)) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}
$data = decrypt(urldecode($data));
if(empty($data)) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}
$data = json_decode($data, true); 
if(json_last_error() !== JSON_ERROR_NONE) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}
$action = isset($data['action']) ? $data['action'] : null;
if($action === 'getTables') {
    LogX('[ api ][ getTables ] Begin');
    $tables = GlobalModel::getTables();
    echo encrypt(json(array('code' => 1, 'msg' => '成功', 'data' => $tables)));
    LogX('[ api ][ getTables ] End');
    exit;
} elseif($action === 'pushTables') {
    LogX('[ api ][ pushTables ] Begin');
    if(!isset($_FILES['file']) || !is_uploaded_file($_FILES['file']['tmp_name'])) {
        echo encrypt(json(array('code' => 0, 'msg' => '没有上传文件')));
        exit;
    }
    $filename = dirname(__FILE__) . '/tmp/'. uniqid() . '.zip';
    create_path(dirname($filename));
    if(!move_uploaded_file($_FILES['file']['tmp_name'], $filename)) {
        echo encrypt(json(array('code' => 0, 'msg' => '移动文件失败')));
        exit;
    }
    LogX('[ api ][ pushTables ] Upload: ' . $filename);
    $zip = new ZipArchive();
    if($zip->open($filename) !== true) {
        echo encrypt(json(array('code' => 0, 'msg' => '压缩包有误')));
        exit;
    }

    $extractPath = dirname($filename) . '/' . pathinfo($filename, PATHINFO_FILENAME);
    create_path($extractPath);
    if($zip->extractTo($extractPath) !== true) {
        echo encrypt(json(array('code' => 0, 'msg' => '解压失败')));
        exit;
    }
    $zip->close();
    $file = scandir($extractPath, 1);
    if(!isset($file[0]) || !is_file($extractPath . '/' .$file[0])) {
        echo encrypt(json(array('code' => 0, 'msg' => '压缩包有误')));
        exit;
    }
    LogX('[ api ][ pushTables ] ZipArchive: ' . $extractPath . '/' . $file[0]);

    $num = GlobalModel::importTables($extractPath . '/' . $file[0]);

    unlink($filename);
    unlink($extractPath . '/' . $file[0]);
    rmdir($extractPath);
    LogX('[ api ][ pushTables ] Unlink: ' . $filename);
    LogX('[ api ][ pushTables ] Unlink: ' . $extractPath . '/' . $file[0]);
    LogX('[ api ][ pushTables ] Rmdir: ' . $extractPath);

    LogX('[ api ][ pushTables ] End');
    echo encrypt(json(array('code' => 1, 'msg' => '成功', 'data' => array('num' => $num))));
    exit;

} elseif($action === 'pullTables') {
    LogX('[ api ][ pullTables ] Begin');
    $tables = isset($data['tables']) ? trim($data['tables']) : '';
    $tables = array_filter(explode(',', $tables));
    if(empty($tables)) {
        echo encrypt(json(array('code' => 0, 'msg' => '请选择数据表')));
        exit;
    }
    try{
        $file = GlobalModel::exportTables($tables);
        LogX('[ api ][ pullTables ] exportTables: ' . $file);

        if(!file_exists($file)) {
            echo encrypt(json(array('code' => 0, 'msg' => '导出失败')));
            exit;
        }
        $filename = dirname(__FILE__) . '/tmp/%s.zip';
        $filename = sprintf($filename, pathinfo($file, PATHINFO_FILENAME));
        create_path(dirname($filename));

        $zip = new ZipArchive();
        if($zip->open($filename, ZipArchive::CREATE) !== true) {
            echo json(array('code' => 0, 'msg' => '创建压缩包失败'));
            exit;
        }
        $zip->addFile($file, pathinfo($file, PATHINFO_BASENAME));
        $zip->close();
        LogX('[ api ][ pullTables ] ZipArchive: ' . $filename);

        //发送数据
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($filename).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filename));
        readfile($filename);
        unlink($filename);
        unlink($file);
        LogX('[ api ][ pullTables ] Unlink: ' . $filename);
        LogX('[ api ][ pullTables ] Unlink: ' . $file);
        LogX('[ api ][ pullTables ] End');
    }catch(\Exception $e) {
        LogX('[ api ][ pullTables ] Exception: ' . $e->getMessage());
        LogX('[ api ][ pullTables ] End');
        echo json(array('code' => 0, 'msg' => $e->getMessage()));
        exit;
    }
} else {
    echo encrypt(json(array('code' => 0, 'msg' => 'Unknown Method')));
    exit;
}
exit;
