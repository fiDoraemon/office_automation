layui.use(['form', 'table'], function () {
    var $ = layui.jquery,
        form = layui.form;

    var projectId = getQueryVariable('projectId');
    console.log("projectId",projectId);

    //获取项目信息
    $.ajax({
        url:"/office_automation/public/index.php/index/admin_c/getProjectOfId",
        type: "get",
        data:{
            projectId : projectId
        },
        success: function(res){
            console.log(res)
            if(res.code !== 0){
                return;
            }
            let data = res.data;
            $("#projectCode").val(data.project_code);
            $("#projectName").val(data.project_name);
            $("#description").val(data.description);
            $("#stage").val(data.doc_stage);
        },
        error: function(res){}
    });

    //确认提交
    form.on('submit(save)', function (data) {
        var projectInfo = data.field;
        projectInfo.project_id = projectId;
        layer.confirm('确定提交？', {icon: 3, title:'提示'}, function(index){
            layer.close(index);
            var loadingIndex = layer.load(2);
            $.ajax({
                url: "/office_automation/public/index.php/index/admin_c/updateProject",
                type:'post',
                data: projectInfo,
                success: function(res){
                    if(res.code !== 0){
                        return;
                    }
                    layer.close(loadingIndex);
                    layer.alert('修改成功！', {title: '提示'},
                        function (index) {
                            layer.close(index);
                            let frameIndex = parent.layer.getFrameIndex(window.name);
                            parent.layer.close(frameIndex);
                            location.reload();
                        }
                    );
                },
                error: function(res){}
            });
        });
        return false;
    });
});