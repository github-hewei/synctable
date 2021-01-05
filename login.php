<?php // CODE BY HW
//登录页面
require_once dirname(__FILE__) . '/src/common.php';
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(post('pass') !== LOGIN_PASS) {
        echo json(array('code' => 0, 'msg' => '登录失败'));
        exit;
    }
    $_SESSION['login'] = true;
    echo json(array('code' => 1, 'msg' => '登录成功', 'data' => array('url' => get('url'))));
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>登录</title>
    <link rel="stylesheet" href="static/bootstrap.min.css">
</head>
<body style="padding: 10px;">
<div class="container" style="width:100%;">
    <div class="row">
        <div class="col-md-4" style="padding:0;">
            <div class="panel panel-primary">
                <div class="panel-heading">
                    <h4 class="panel-title">登录</h4>
                </div>
                <div class="panel-body">
                    <form action="" class="form" role="form">
                        <div class="form-group" style="margin-bottom: 0px;">
                            <input type="password" class="form-control" name="pass" autocomplete="off" placeholder="请输入登录密码">
                        </div>
                    </form>
                </div>
                <div class="panel-footer">
                    <button type="button" class="btn btn-sm btn-primary btn-submit">登录</button>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
<script src="static/jquery-1.11.1.min.js"></script>
<script src="static/layer/layer.js"></script>
<script src="static/bootstrap.min.js"></script>
<script>
$(function() {
    $(".btn-submit").on("click", function() {
        $.ajax({
            type: "post",
            url: window.location.href,
            dataType: 'json',
            data: {
                pass: $.trim($('[name=pass]').val())
            },
            async: false,
            success: function(data) {
                if(data.code == 1) {
                    window.location.href = data.data.url;
                } else {
                    alert(data.msg);
                }
            },
            error: function() {
                alert('请求异常');
            }
        });
    });
});
</script>
