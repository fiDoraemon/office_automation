layui.use(['form', 'table'], function () {
    var $ = layui.jquery,
        form = layui.form;

    //确认提交
    form.on('submit(save)', function (data) {
        var projectInfo = data.field;
        console.log(projectInfo)
        layer.confirm('确定提交？', {icon: 3, title:'提示'}, function(index){
            layer.close(index);
            var loadingIndex = layer.load(2);
            $.ajax({
                url: "/office_automation/public/index.php/index/admin_c/saveProject",
                type:'post',
                data: projectInfo,
                success: function(res){
                    if(res.code !== 0){
                        return;
                    }
                    layer.close(loadingIndex);
                    layer.alert('添加成功！', {title: '提示'},
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