layui.use([ 'form', 'step'], function () {
    var $ = layui.$,
        form = layui.form,
        step = layui.step;

    step.render({
        elem: '#stepForm',
        filter: 'stepForm',
        width: '100%', //设置容器宽度
        stepWidth: '750px',
        height: '500px',
        stepItems: [{
            title: '填写员工信息'
        }, {
            title: '填写验证信息'
        }, {
            title: '完成'
        }]
    });

    form.on('submit(formStep)', function (data) {
        var userId = $("#user-id").val();
        var userEmail = $("#user-email").val();
        var codeType = $("#code-type").val();
        $.ajax({
            url: "/office_automation/public/index.php/index/login_c/sendEmailCode",
            type:'post',
            timeout: 30000,
            data: {
                userId    : userId,
                userEmail : userEmail,
                codeType  : codeType
            },
            success: function(data){
                console.log(data);
                if(data["code"] == 9){ //email地址与数据库一致
                    $("#info-user-id").html(userId);
                    $("#info-user-email").html(userEmail);
                    step.next('#stepForm');
                }else if(data["code"] == 11){
                    var index = layer.alert("员工工号或邮箱地址错误！", {
                        title: '系统提示'
                    });
                }else{
                    var index = layer.alert("验证码发送错误！", {
                        title: '系统提示'
                    });
                }
            },
            error: function(data){
                var index = layer.alert("系统错误！", {
                    title: '系统提示'
                });
            }
        });
        return false;
    });

    form.on('submit(formStep2)', function (data) {
        console.log(data);
        var code = $("#email-code").val();
        var newPwd = $("#new-Pwd").val();
        var enterPwd = $("#enter-pwd").val();
        var userId = $("#user-id").val();
        if(newPwd === enterPwd){
            $.ajax({
                url: "/office_automation/public/index.php/index/login_c/retrievePwd",
                type:'post',
                timeout: 1000,
                data: {
                    code      : code,
                    newPwd    : newPwd,
                    userId    : userId
                },
                success: function(data){
                    if(data["code"] == 13){
                        step.next('#stepForm');
                    }else if(data["code"] == 14){
                        var index = layer.alert("验证码填写错误！", {
                            title: '系统提示'
                        });
                    }else{
                        var index = layer.alert("系统错误！", {
                            title: '系统提示'
                        });
                    }
                },
                error: function(data){
                    var index = layer.alert("系统错误！", {
                        title: '系统提示'
                    });
                }
            });
        }else{
            var index = layer.alert("两次密码输入不一致！", {
                title: '系统提示'
            });
        }
        return false;
    });

    $('.return-login').click(function () {
        window.location = '../login.html';
    });

    $('.pre').click(function () {
        step.pre('#stepForm');
    });

    $('.next').click(function () {
        step.next('#stepForm');
    });
})