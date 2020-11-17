layui.use(['form', 'table'], function () {
    var $ = layui.jquery,
        form = layui.form,
        table = layui.table;

    var departmentInfo = "";

    table.render({
        elem: '#userList',
        url: '/office_automation/public/index.php/index/admin_c/getAllUsers',
        toolbar: '#toolbar',
        defaultToolbar: ['filter', 'exports', 'print', {
            title: '提示',
            layEvent: 'LAYTABLE_TIPS',
            icon: 'layui-icon-tips'
        }],
        cols: [[
            {field: 'user_id', title: '工号', sort: true},
            {field: 'user_name', title: '姓名'},
            {field: 'department_name', title: '部门'},
            {field: 'phone', title: '手机'},
            {field: 'email', title: '邮箱'},
            {field: 'status', title: '用户状态'},
            {field: 'create_time', title: '创建时间', sort: true},
            {field: 'update_time', title: '修改时间', sort: true},
            {title: '操作', toolbar: '#userListBar', align: "center"}
        ]],
        id: 'userList',
        limits: [10, 15, 20, 25, 50, 100],
        limit: 15,
        page: true,
        skin: 'line'
    });

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
            var departmentArray = res.data;;
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

    // 监听搜索操作
    form.on('submit(data-search-btn)', function (data) {
        var result = data.field;
        //执行搜索重载
        table.reload('userList', {
            page: {
                curr: 1
            }
            , where: {
                userName: result.user_name,
                departmentId: result.department_id,
                userStatus: result.user_status
            }
        }, 'data');
        return false;
    });

    /**
     * toolbar监听事件
     */
    table.on('toolbar(userListFilter)', function (obj) {
        if (obj.event === 'add') {  // 监听添加操作
            var index = layer.open({
                title: '添加用户',
                type: 2,
                shade: 0.2,
                maxmin:true,
                shadeClose: true,
                area: ['100%', '100%'],
                content: 'add.html?v=5',
            });
            $(window).on("resize", function () {
                layer.full(index);
            });
        } else if (obj.event === 'update') {
            layer.confirm('确定要更新？', function (index) {
                var loadingIndex = layer.load(2);
                $.ajax({
                    url: "/office_automation/public/index/admin_c/updateUserid",
                    type:'post',
                    data: {},
                    success: function(res){
                        layer.close(loadingIndex);
                        if(res.code === 0){
                            console.log("success")
                            layer.msg('更新成功!', function() {});
                        }else{
                            console.log("error")
                            layer.msg("更新失败!", function() {});
                        }
                    },
                    error: function(res){
                        console.log("error!")
                    }
                });
                layer.close(index);
            });
        }
    });

    //监听表格复选框选择
    table.on('checkbox(userListFilter)', function (obj) {
        console.log(obj)
    });

    table.on('tool(userListFilter)', function (obj) {
        var data = obj.data;
        if (obj.event === 'edit') {
            var index = layer.open({
                title: '编辑用户',
                type: 2,
                shade: 0.2,
                maxmin:true,
                shadeClose: true,
                area: ['100%', '100%'],
                content: 'user-edit.html?user_id=' + data["user_id"],
            });
            $(window).on("resize", function () {
                layer.full(index);
            });
            return false;
        } else if (obj.event === 'stop') {
            layer.confirm('确定要禁用？', function (index) {
                $.ajax({
                    url: "/office_automation/public/index/admin_c/deleteUser",
                    type:'post',
                    data: {
                        user_id : data["user_id"]
                    },
                    success: function(res){
                        if(res.code === 0){
                            layer.msg('禁用成功!', function() {});
                        }else{
                            layer.msg("禁用失败!", function() {});
                        }
                    },
                    error: function(res){
                        layer.msg("禁用失败!", function() {});
                    }
                });
                layer.close(index);
            });
        }
    });

});