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
        timeout: 2000,
        data: {
        },
        success: function(res){
            var departmentArray = res.data;
            for (var i = 0; i < departmentArray.length; i++){
                departmentInfo += "<option value='" + departmentArray[i]["department_id"] + "'>" + departmentArray[i]["department_name"] + "</option>";
            }
            $("#departmentSelect").append(departmentInfo);
            //需要重新加载
            form.render('select');
        },
        error: function(res){
        }
    });

    //监听提交
    form.on('submit(saveBtn)', function (data) {
        var result = data.field;
        layer.confirm('确定提交？', {icon: 3, title:'提示'}, function(index){
            layer.close(index);
            var loadingIndex = layer.load(2);
            $.ajax({
                url: "/office_automation/public/index.php/index/admin_c/addUser",
                type:'post',
                data: {
                    user_id         : result.user_id,
                    user_name       : result.user_name,
                    department_id   : result.department_id,
                    phone           : result.phone,
                    email           : result.email
                },
                success: function(res){
                    layer.close(loadingIndex);
                    switch (res.code) {
                        case 0:
                            var index =layer.alert('添加成功！', {title: '提示'},
                                function () {
                                    layer.close(index);
                                    var frameIndex = parent.layer.getFrameIndex(window.name);
                                    parent.layer.close(frameIndex);
                                }
                            );
                            break;
                        case 3:
                            layer.msg('没有添加用户权限！');
                            break;
                        case 31:
                            layer.msg('员工ID已经存在！');
                            break;
                        default:
                            layer.msg('添加失败！');
                            break;
                    }
                },
                error: function(res){}
            });
        });
        return false;
    });
});