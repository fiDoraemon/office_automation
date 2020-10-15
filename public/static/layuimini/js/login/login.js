keep_login = 0;  //0代表不保持登录，1代表保持登录
//自动token登录
var token = $.cookie('user_token');
if(token != "" && token != null){ //检查cookie中是否存在token信息
    $.ajax({
        url: "/office_automation/public/index.php/index/login_c/tokenLogin",
        type:'post',
        timeout: 1000,//超时时间
        data: {
            userToken : token
        },
        success: function(res){
            if(res["code"] == 16){
                //token登录成功,跳转页面
                window.location = '../index.html';
            }
        },
        error: function(res){
            console.log(res);
        }
    });
}

//实现点击刷新验证码功能
// $('#refreshCaptcha').on('click', function(){
//     var path = "/office_automation/public/index.php/index/login_c/sendPageCode";
//     $("#refreshCaptcha").attr('src',path);
// });

//发送登录请求
function sendData(){
    var userNum  = $("#user-num").val();
    var userPwd  = $("#user-pwd").val();
    var pageCode = $("#page-code").val();
    $.ajax({
        url: "/office_automation/public/index.php/index/login_c/login",
        type:'post',
        //dataType: 'json',//返回的内容的类型，由于PHP文件是直接echo的，那么这里就是text
        timeout: 1000,//超时时间
        data: {
            userNum  : userNum,
            userPwd  : userPwd,
            pageCode : pageCode,
            keepLogin: keep_login
        },
        success: function(res){
            var code = res['code'];
            console.log(res);
            var user_token = res["data"];
            switch (code) {
                case 0:
                    if(keep_login === 1){
                        $.cookie('user_token',user_token,{path:'/office_automation/public/static/layuimini', expires: 7 });
                    }
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
    // window.location = '../index.html';
    layer.msg('登录成功' ,{time: 500},function() {
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


layui.use(['form','jquery'], function () {
    var $ = layui.jquery,
        form = layui.form,
        layer = layui.layer;

    //跳转到找回密码页面
    $('.forget-password').on('click', function () {
        window.location = 'user/retrieve-passworld.html';
    });

    // 登录过期的时候，跳出ifram框架
    if (top.location != self.location) top.location = self.location;

    $('.bind-password').on('click', function () {
        if ($(this).hasClass('icon-5')) {
            $(this).removeClass('icon-5');
            $("input[name='password']").attr('type', 'password');
        } else {
            $(this).addClass('icon-5');
            $("input[name='password']").attr('type', 'text');
        }
    });

    $('.icon-nocheck').on('click', function () {
        if ($(this).hasClass('icon-check')) {
            keep_login = 0;
            $(this).removeClass('icon-check');
        } else {
            keep_login = 1;
            $(this).addClass('icon-check');
        }
    });

    // 进行登录操作
    form.on('submit(login)', function (data) {
        data = data.field;
        if (data.username == '') {
            layer.msg('用户名不能为空');
            return false;
        }
        if (data.password == '') {
            layer.msg('密码不能为空');
            return false;
        }
        if (data.captcha == '') {
            layer.msg('验证码不能为空');
            return false;
        }
        sendData();
        return false;
    });

});