layui.use(['form','miniTab'], function () {
    var form = layui.form,
        layer = layui.layer,
        miniTab = layui.miniTab;

    //监听提交
    form.on('submit(saveBtn)', function (data) {
        if(data.field['again_password'] != data.field['new_password']){
            var index = layer.alert("两次密码输入不一致！", {
                title: '系统提示'
            });
            return ;
        }
        $.ajax({
            url: "/office_automation/public/index.php/index/user_c/changePwd",
            type:'post',
            timeout: 1000,
            data: {
                oldPwd: data.field['old_password'],
                newPwd: data.field['new_password']
            },
            success: function(data){
                switch (data['code']) {
                    case 0:
                        var index = layer.alert("密码修改成功！", {
                            title: '系统提示'
                        }, function () {
                            layer.close(index);
                            miniTab.openNewTabByIframe({
                                href:"page/mission/index.html",
                                title:"我的任务",
                            });
                        });
                        break;
                    case 8:
                        var index = layer.alert("原密码输入错误！", {
                            title: '系统提示'
                        });
                        break;
                    default:
                        var index = layer.alert("密码修改失败！", {
                            title: '系统提示'
                        });
                        break;
                }
            },
            error: function(data){
                var index = layer.alert("请求出错！", {
                    title: '系统提示'
                });
            }
        });
        return false;
    });

});