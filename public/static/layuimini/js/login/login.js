//实现点击刷新验证码功能
$('#refreshCaptcha').on('click', function(){
    var path = "/office_automation/public/index.php/index/login_controller/sendPageCode";
    $("#refreshCaptcha").attr('src',path);
});

//发送登录请求
function sendData(){
    var userNum  = $("#user-num").val();
    var userPwd  = $("#user-pwd").val();
    var pageCode = $("#page-code").val();
    $.ajax({
        url: "/office_automation/public/index.php/index/login_controller/login",
        type:'post',
        //dataType: 'json',//返回的内容的类型，由于PHP文件是直接echo的，那么这里就是text
        timeout: 1000,//超时时间
        data: {
            userNum  : userNum,
            userPwd  : userPwd,
            pageCode : pageCode
        },
        success: function(data){
            var code = data['code'];
            switch (code) {
                case 0:
                    loginSuccess();
                    break;
                case 1:
                    loginError();
                    break;
                case 2:
                    loginFail();
                    break;
                case 14:
                    pageCodeError();
                    break;
            }
        },
        error: function(data){
            console.log(data);
            console.log("error");
        }
    });
}

/**
 * 登录成功，跳转页面
 */
function loginSuccess(){
    layer.msg('登录成功', function() {
        window.location = '../index.html';
    });
}

/**
 * 用户填写信息有误导致登录失败
 */
function loginFail(){
    layer.msg('用户名或密码有误！', function() {

    });
}

/**
 * 系统出错导致的登录失败
 */
function loginError(){
    layer.msg('系统繁忙', function() {

    });
}

/**
 * 验证码输入错误
 */
function pageCodeError(){
    layer.msg('验证码输入错误！', function() {

    });
}