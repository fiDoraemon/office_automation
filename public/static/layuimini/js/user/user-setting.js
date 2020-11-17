layui.use(['form','miniTab'], function () {
    var $ = layui.jquery,
        form = layui.form,
        layer = layui.layer,
        miniTab = layui.miniTab;

    var departmentInfo = "";
    /**
     * 获取部门信息
     */
    $.ajax({
        url: "/office_automation/public/index.php/index/minute_c/getAllDepartment",
        type:'get',
        data: {
        },
        success: function(res){
            var departmentArray = res.data;;
            for (var i = 0; i < departmentArray.length; i++){
                departmentInfo += "<option value='" + departmentArray[i]["department_id"] + "'>" + departmentArray[i]["department_name"] + "</option>";
            }
            $("#departmentSelect").append(departmentInfo);
            //进入页面马上获取用户的基本信息
            $.ajax({
                url: "/office_automation/public/index.php/index/user_c/getUserInfo",
                type:'get',
                data: {
                },
                success: function(data){
                    var code = data.code;
                    //获取成功，显示用户信息
                    if(code === 0){
                        var user_info = data['data'];
                        var user_id = user_info['user_id'];
                        var user_name = user_info['user_name'];
                        var user_department = user_info['department_id'];
                        var user_phone = user_info['phone'];
                        var user_email = user_info['email'];
                        $("#user_id").val(user_id);
                        $("#user_name").val(user_name);
                        $("#departmentSelect").val(user_department);
                        $("#user_phone").val(user_phone);
                        $("#user_email").val(user_email);
                        form.val("setValue", {
                            'dd_open': (user_info['dd_open'] == 1)? true : false
                        });
                        //需要重新加载
                        form.render('select');
                    }
                },
                error: function(res){
                    var index = layer.alert("请求出错！", {
                        title: '系统提示'
                    });
                }
            });
            //需要重新加载
            form.render('select');
        },
        error: function(res){
        }
    });

    //监听提交
    form.on('submit(saveBtn)', function (data) {
        var userName     = $("#user_name").val().trim().replace(/\s/g,"");
        var departmentId = $("#departmentSelect").val();
        var userPhone    = $("#user_phone").val().trim().replace(/\s/g,"");
        var userEmail    = $("#user_email").val().trim().replace(/\s/g,"");
        var ddOpen       = data.field.dd_open? 1 : 0;

        layer.confirm('确定提交？', {icon: 3, title:'提示'}, function(index){
            layer.close(index);
            $.ajax({
                url: "/office_automation/public/index.php/index/user_c/updateUserInfo",
                type:'post',
                data:{
                    userName    : userName,
                    departmentId: departmentId,
                    userPhone   : userPhone,
                    userEmail   : userEmail,
                    ddOpen      : ddOpen
                },
                success: function(data){
                    var code = data['code'];
                    if(code === 0){
                        var index = layer.alert("用户信息修改成功", {
                            title: '系统提示'
                        }, function () {
                            layer.close(index);
                            miniTab.openNewTabByIframe({
                                href:"page/mission/index.html",
                                title:"我的任务",
                            });
                        });
                    }else{
                        var index = layer.alert("用户信息修改失败！", {
                            title: '系统提示'
                        });
                    }
                },
                error: function(data){
                    var index = layer.alert("请求出错！", {
                        title: '系统提示'
                    });
                }
            });
        });
        return false;
    });
});