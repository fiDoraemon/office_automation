showZoomImg('.zoomImg', 'img');
showZoomImg('.zoomImgshow', 'text');
layui.use(['form','miniTab','upload'], function () {
    var $       = layui.jquery,
        form    = layui.form,
        layer   = layui.layer,
        miniTab = layui.miniTab;

    uploadFileIf = [];  //已上传的文件id,必须为全局的

    var hasData = 0;

    $("#material-code").bind("input propertychange", function() {
        var matCode = $("#material-code").val();
        if(matCode.length !== 13){
            hasData = 0;
            $("#material-name").val("无此编码");
            return;
        }
        $.ajax({
            url: "/office_automation/public/index.php/index/iqc_c/getMaterialNameByCode",
            type: "get",
            data:{
                matCode : matCode
            },
            success: function(res){
                if(res.code === 0){
                    hasData = 1;
                    $("#material-name").val(res.data);
                }else{
                    hasData = 0;
                    $("#material-name").val("无此编码");
                }
            },
            error: function(res){}
        });
    });

    //监听提交
    form.on('submit(save)', function (data) {
        if(uploadFileIf.length < 1){
            layer.msg("请先上传缺陷图片", {icon: 5});
            return false;
        }
        if(hasData === 0){
            layer.msg("数据库中没有对应的物料信息", {icon: 5});
            return false;
        }
        var result = data.field;
        result.file = getObjectValues(uploadFileIf);
        layer.confirm('确定提交缺陷？', {icon: 3, title:'提示'}, function(index){
            layer.close(index);
            var loadingIndex = layer.load(2);
            $.ajax({
                url: "/office_automation/public/index.php/index/iqc_c/saveIQC",
                type:'post',
                data: result,
                success: function(res){
                    layer.close(loadingIndex);
                    if(res.code === 0){
                        var index = layer.alert("发起成功", {
                            title: '提示'
                        }, function () {
                            layer.close(index);
                            location.reload();
                            miniTab.openNewTabByIframe({
                                href:"page/iqc/index-iqc.html",
                                title:"IQC缺陷浏览",
                            });
                        });
                    }else{
                        var index = layer.alert("提交失败！", {
                            title: '提示'
                        });
                    }
                },
                error: function(res){}
            });
        });
        return false;
    });

    function getObjectValues(object)
    {
        var values = [];
        for (var property in object){
            values.push(object[property]);
        }
        return values;
    }
});