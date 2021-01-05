<?php // CODE BY HW
//首页
require_once dirname(__FILE__) . '/src/common.php';
if(!isset($_SESSION['login']) || !$_SESSION['login']) {
    header('location: ' . LOCAL_URL . '/login.php?url=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = post('action');
    if($action === 'getTables') {
        LogX('[ index ][ getTables ] Begin');
        $localTables = GlobalModel::getTables();
        $remoteTables = array();
        try{
            $url = REMOTE_URL . '/api.php';
            LogX('[ index ][ getTables ] Request: ' . $url);
            $curl = new cURL();
            $curl->setUrl($url);
            $curl->setPost();
            $curl->setData(array('data' => urlencode(encrypt(json(array('action' => 'getTables'))))));
            $curl->send();
            $content = $curl->getContent();
            if(empty($content)) {
                echo json(array('code' => 0, 'msg' => '接口响应异常'));
                exit;
            }
            $decContent = decrypt($content);
            if(empty($decContent)) {
                echo json(array('code' => 0, 'msg' => '解码失败'));
                exit;
            }
            $data = json_decode($decContent, true);
            if(!isset($data['code'])) {
                echo json(array('code' => 0, 'msg' => '接口返回数据错误'));
                exit;
            }
            if($data['code'] !== 1) {
                echo json($data);
                exit;
            }
            $remoteTables = $data['data'];
            LogX('[ index ][ getTables ] Request: OK');
        }catch(\Exception $e) {
            LogX('[ index ][ getTables ] Exception: ' . $e->getMessage());
        }
        //取数据表交集
        $intersect = array_intersect(array_column($localTables, 'TABLE_NAME'), array_column($remoteTables, 'TABLE_NAME'));
        $tables = array();
        foreach($intersect as $item) {
            $tables[$item] = true;
        }
        foreach($localTables as &$table) {
            $table['FLAG'] = isset($tables[$table['TABLE_NAME']]) ? false : true;
        }
        unset($table);
        foreach($remoteTables as &$table) {
            $table['FLAG'] = isset($tables[$table['TABLE_NAME']]) ? false : true;
        }
        unset($table);
        LogX('[ index ][ getTables ] End');
        echo json(array('code' => 1, 'msg' => '请求成功', 'data' => array(
            'localTables' => $localTables,
            'remoteTables' => $remoteTables,
        )));
        exit;
    } elseif($action === 'pushTables') {
        LogX('[ index ][ pushTables ] Begin');
        $tables = array_filter(explode(',', post('tables')));
        if(empty($tables)) {
            echo json(array('code' => 0, 'msg' => '请选择数据表'));
            exit;
        }
        try{
            $file = GlobalModel::exportTables($tables);
            LogX('[ index ][ pushTables ] exportTables: ' . $file);
            if(!file_exists($file)) {
                echo json(array('code' => 0, 'msg' => '导出失败'));
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
            LogX('[ index ][ pushTables ] ZipArchive: ' . $filename);
            $url = REMOTE_URL . '/api.php';
            LogX('[ index ][ pushTables ] Request: ' . $url);
            $curl = new cURL();
            $curl->setUrl($url);
            $curl->setPost();
            $curl->setData(array('data' => urlencode(encrypt(json(array('action' => 'pushTables'))))));
            $curl->addFile($filename);
            $curl->send();
            $content = $curl->getContent();
            if(empty($content)) {
                echo json(array('code' => 0, 'msg' => '接口响应异常'));
                exit;
            }
            $decContent = decrypt($content);
            if(empty($decContent)) {
                echo json(array('code' => 0, 'msg' => '解码失败'));
                exit;
            }
            $data = json_decode($decContent, true);
            if(!isset($data['code'])) {
                echo json(array('code' => 0, 'msg' => '接口返回数据错误'));
                exit;
            }
            LogX('[ index ][ pushTables ] Request: OK');
            unlink($filename);
            unlink($file);
            LogX('[ index ][ pushTables ] Unlink: ' . $filename);
            LogX('[ index ][ pushTables ] Unlink: ' . $file);
            LogX('[ index ][ pushTables ] End');
            echo json($data);
            exit;
        }catch(\Exception $e) {
            echo json(array('code' => 0, 'msg' => $e->getMessage()));
            LogX('[ index ][ pushTables ] Exception: ' . $e->getMessage());
            LogX('[ index ][ pushTables ] End');
            exit;
        }
    } elseif($action === 'pullTables') {
        LogX('[ index ][ pullTables ] Begin');
        $tables = array_filter(explode(',', post('tables')));
        if(empty($tables)) {
            echo json(array('code' => 0, 'msg' => '请选择数据表'));
            exit;
        }
        try {
            $url = REMOTE_URL . '/api.php';
            LogX('[ index ][ pullTables ] Request: ' . $url);
            $curl = new cURL();
            $curl->setUrl($url);
            $curl->setPost();
            $curl->setData(array('data' => urlencode(encrypt(json(array('action' => 'pullTables', 'tables' => implode(',', $tables)))))));
            $curl->send();
            $content = $curl->getContent();
            if($curl->getInfo("content_type") == "application/octet-stream") {
                $filename = dirname(__FILE__) . '/tmp/' . uniqid() . '.zip';
                create_path(dirname($filename));
                file_put_contents($filename, $content);
                LogX('[ index ][ pullTables ] Response: ' . $filename);

                $zip = new ZipArchive();
                if($zip->open($filename) !== true) {
                    echo json(array('code' => 0, 'msg' => '打开压缩包失败'));
                    exit;
                }
                $extractPath = dirname($filename) . '/' . pathinfo($filename, PATHINFO_FILENAME);
                create_path($extractPath);
                if($zip->extractTo($extractPath) !== true) {
                    echo json(array('code' => 0, 'msg' => '解压失败'));
                    exit;
                }
                $zip->close();
                $file = scandir($extractPath, 1);
                if(!isset($file[0]) || !is_file($extractPath . '/' .$file[0])) {
                    echo json(array('code' => 0, 'msg' => '压缩包有误'));
                    exit;
                }
                $num = GlobalModel::importTables($extractPath . '/' . $file[0]);
                LogX('[ index ][ pullTables ] ZipArchive: ' . $extractPath . '/' . $file[0]);

                unlink($filename);
                //unlink($extractPath . '/' . $file[0]);
                //rmdir($extractPath);
                LogX('[ index ][ pullTables ] Unlink: ' . $filename);
                LogX('[ index ][ pullTables ] Unlink: ' . $extractPath . '/' . $file[0]);
                LogX('[ index ][ pullTables ] Rmdir: ' . $extractPath);
                LogX('[ index ][ pullTables ] End');

                echo json(array('code' => 1, 'msg' => '成功', 'data' => array('num' => $num)));
                exit;
            } else {
                $decContent = decrypt($content);
                if(empty($decContent)) {
                    echo json(array('code' => 0, 'msg' => '解码失败'));
                    exit;
                }
                $data = json_decode($decContent, true);
                if(!isset($data['code'])) {
                    echo json(array('code' => 0, 'msg' => '接口返回数据错误'));
                    exit;
                }
                LogX('[ index ][ pullTables ] Response: ' . str_replace("\n", "", print_r($data, true)));
                LogX('[ index ][ pullTables ] End');

                echo json($data);
                exit;
            }
        }catch(\Exception $e) {
            LogX('[ index ][ pullTables ] Exception: ' . $e->getMessage());
            LogX('[ index ][ pullTables ] End');
            echo json(array('code' => 0, 'msg' => $e->getMessage()));
            exit;
        }
    } else {
        echo json(array('code' => 0, 'msg' => 'Unknown Method'));
        exit;
    }
    exit;
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>数据表同步</title>
    <link rel="stylesheet" href="static/bootstrap.min.css">
</head>
<body style="padding: 10px;">
    <div class="container" style="width: 100%;">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h4 class="panel-title">数据表同步</h4>
                    </div>
                    <div class="panel-body">
                        <div class="col-md-6">
                            <table id="local-tables" class="table table-bordered table-condensed table-hover">
                                <thead>
                                    <tr>
                                        <td colspan="4">
                                            <div class="col-md-6">
                                                <span>本地数据表</span>
                                            </div>
                                            <div class="col-md-6" style="text-align:right;">
                                                <a class="btn-push-batch" href="javascript:;">批量推送</a>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="info">
                                        <td><input class="input-check-all" type="checkbox"> </td>
                                        <td>数据表</td>
                                        <td>数据量</td>
                                        <td>操作</td>
                                    </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table id="remote-tables" class="table table-bordered table-condensed table-hover">
                                <thead>
                                <tr>
                                    <td colspan="4">
                                        <div class="col-md-6">
                                            <span>远程数据表
                                                <span style="color:red;"><?php echo REMOTE_URL; ?></span>
                                            </span>
                                        </div>
                                        <div class="col-md-6" style="text-align:right;">
                                            <a class="btn-pull-batch" href="javascript:;">批量拉取</a>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="info">
                                    <td><input class="input-check-all" type="checkbox"> </td>
                                    <td>数据表</td>
                                    <td>数据量</td>
                                    <td>操作</td>
                                </tr>
                                </thead>
                                <tbody>

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
<!--            <div class="col-md-0">-->
<!--                <div class="panel panel-primary">-->
<!--                    <div class="panel-heading">-->
<!--                        <h4 class="panel-title">操作日志</h4>-->
<!--                    </div>-->
<!--                    <div class="panel-body">-->
<!---->
<!--                    </div>-->
<!--                </div>-->
<!--            </div>-->
        </div>
    </div>

</body>
</html>
<script src="static/jquery-1.11.1.min.js"></script>
<script src="static/layer/layer.js"></script>
<script src="static/bootstrap.min.js"></script>
<script>
$(function() {
    //获取数据表列表
    $.ajax({
        url: "<?php echo LOCAL_URL . '/index.php' ?>",
        type: 'post',
        data: {"action": "getTables"},
        dataType: "json",
        success: function(res) {
            if(res.code == 1) {
                var hl = '';
                $.each(res.data.localTables, function(k, row) {
                    hl += '<tr data-table="'+ row['TABLE_NAME'] +'">';
                    hl += '<td> <input class="input-check" type="checkbox"> </td>';
                    hl += '<td ';
                    if(row['FLAG']) {
                        hl += 'style="color:green;"';
                    }
                    hl += ' >' + row['TABLE_NAME'] + '</td>';
                    hl += '<td>' + row['TABLE_ROWS'] + '</td>';
                    hl += '<td align="center"> <a class="btn-push" href="javascript:;">推送</a> </td>';
                    hl += '</tr>';
                });
                $('#local-tables tbody').html(hl);

                var hr = '';
                $.each(res.data.remoteTables, function(k, row) {
                    hr += '<tr data-table="'+ row['TABLE_NAME'] +'">';
                    hr += '<td> <input class="input-check" type="checkbox"> </td>';
                    hr += '<td ';
                    if(row['FLAG']) {
                        hr += 'style="color:red;"';
                    }
                    hr += ' >' + row['TABLE_NAME'] + '</td>';
                    hr += '<td>' + row['TABLE_ROWS'] + '</td>';
                    hr += '<td align="center"> <a class="btn-pull" href="javascript:;">拉取</a> </td>';
                    hr += '</tr>';
                });
                $('#remote-tables tbody').html(hr);

            } else {
                alert(res.msg);
            }
        },
        error: function() {
            alert('请求异常');
        }
    });

    //全选/取消全选效果
    $('.input-check-all').on('click', function() {
        var table = $(this).closest('table');
        if($(this).prop('checked')) {
            table.find('.input-check').prop('checked', true);
        } else {
            table.find('.input-check').prop('checked', false);
        }
    });

    //单表推送
    $(document).on('click', '.btn-push', function() {
        var table = $(this).closest('tr').data('table');
        Push([table]);
    });

    //批量推送
    $(document).on('click', '.btn-push-batch', function() {
        var tables = [];
        $('#local-tables .input-check').each(function() {
            if($(this).prop('checked')) {
                tables.push($(this).closest('tr').data('table'));
            }
        });
        if(tables.length == 0) {
            layer.msg('请选择数据表');
            return false;
        }
        Push(tables);
    });

    //单表拉取
    $(document).on('click', '.btn-pull', function() {
        var table = $(this).closest('tr').data('table');
        Pull([table]);
    });

    //批量拉取
    $(document).on('click', '.btn-pull-batch', function() {
        var tables = [];
        $('#remote-tables .input-check').each(function() {
            if($(this).prop('checked')) {
                tables.push($(this).closest('tr').data('table'));
            }
        });
        if(tables.length == 0) {
            layer.msg('请选择数据表');
            return false;
        }
        Pull(tables);
    });

    //推送数据表
    function Push(tables) {
        var index = layer.load(1);
        $.ajax({
            type: 'post',
            url: "<?php echo LOCAL_URL . '/index.php' ?>",
            data: {
                "action": "pushTables",
                "tables": tables.join(',')
            },
            dataType: "json",
            async: false,
            success: function(data) {
                layer.close(index);
                if(data.code == 1) {
                    layer.alert("推送完成，共执行 " +data.data.num+ " 条SQL", function() {
                        window.location.reload();
                    });
                } else {
                    layer.msg(data.msg);
                }
            },
            error: function() {
                layer.close(index);
                layer.msg('请求异常');
            }
        });
    }

    //拉取数据表
    function Pull(tables) {
        var index = layer.load(1);
        $.ajax({
            type: 'post',
            url: "<?php echo LOCAL_URL . '/index.php' ?>",
            data: {
                "action": "pullTables",
                "tables": tables.join(',')
            },
            dataType: "json",
            async: false,
            success: function(data) {
                layer.close(index);
                if(data.code == 1) {
                    layer.alert("拉取完成，共执行 " +data.data.num+ " 条SQL", function() {
                        window.location.reload();
                    });
                } else {
                    layer.msg(data.msg);
                }
            },
            error: function() {
                layer.close(index);
                layer.msg('请求异常');
            }
        });
    }
});
</script>
