//获取url中的参数
function getUrlParam(name) {
    var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)"); //构造一个含有目标参数的正则表达式对象
    var r = window.location.search.substr(1).match(reg);  //匹配目标参数
    if (r != null) return unescape(r[2]); return null; //返回参数值
}

// 计算文件大小函数(保留两位小数),Size为字节大小
// size：初始文件大小
function getfilesize(size) {
    if (!size)
        return "";
    var num = 1024.00; //byte
    if (size < num)
        return size + "B";
    if (size < Math.pow(num, 2))
        return (size / num).toFixed(2) + "K"; //kb
    if (size < Math.pow(num, 3))
        return (size / Math.pow(num, 2)).toFixed(2) + "M"; //M
    if (size < Math.pow(num, 4))
        return (size / Math.pow(num, 3)).toFixed(2) + "G"; //G
    return (size / Math.pow(num, 4)).toFixed(2) + "T"; //T
}


layui.use(['form','miniTab'], function () {
    var $ = layui.jquery,
        form = layui.form,
        layer = layui.layer,
        miniTab = layui.miniTab;

    var requestId = getUrlParam('requestId');

    if(requestId != null){
        $("#requestId").val(requestId);
        var loadingIndex = layer.load(2);
        $.ajax({
            url: "/office_automation/public/index.php/index/document_c/getRequestInfo",
            type:'get',
            data: {
                requestId : requestId
            },
            success: function(res){
                layer.close(loadingIndex);
                if(res.code === 0){
                    var data = res.data;
                    $("#projectCode").val(data.project_code);
                    $("#projectStage").val(data.stage);
                    $("#author").val(data.author_name);
                    $("#approver").val(data.approver_name);
                    $("#request_date").val(data.request_time);
                    $("#remark").val(data.remark);
                    $("#reviewOpinion").val(data.review_opinion);
                    $("#reviewDate").val(data.review_time);
                    //上传附件
                    var attachmentList = data.attachments;
                    element = '';
                    for(i in attachmentList) {
                        var size = getfilesize(attachmentList[i].file_size);
                        element += `
                        <tr>
                            <td>${attachmentList[i].source_name}</td>
                            <td>${size}</td>
                            <td>已上传</td>
                            <td>
                                <a class="layui-btn layui-btn-xs download-btn" href="/Office_Automation/public/upload/${attachmentList[i].save_path}" download="${attachmentList[i].source_name}">下载</a>
                            </td>
                        </tr>
                        `;
                    }
                    $('#fileList').append(element);
                    if(data.status === 0){
                        $("#status").append('<span style=\"color:#01AAED;font-size: 25px\" > 待审批</span>');
                    }else if(data.status === 1){
                        $(".download-btn").remove();
                        $("#status").append('<span style="color:#009688;font-size: 25px" > 已通过</span>');
                    }else{
                        $(".download-btn").remove();
                        $("#status").append('<span style="color:#FF5722;font-size: 25px" > 已驳回</span>');
                    }
                    if(data.isAuthor === 0){
                        $("#form-btn").remove();
                        $("#reviewOpinion").attr("disabled",true);
                    }else{
                        $("#reviewDate-item").remove();
                    }
                }else{
                    var index = layer.alert("数据获取失败！", {
                        title: '提示'
                    });
                }
            },
            error: function(res){}
        });
    }

    //监听评审通过按钮
    form.on('submit(passBtn)', function (data) {
        var result = data.field;
        layer.confirm('确定通过评审？', {icon: 3, title:'提示'}, function(index){
            layer.close(index);
            var loadingIndex = layer.load(2);
            $.ajax({
                url: "/office_automation/public/index.php/index/document_c/passRequest",
                type:'post',
                data: {
                    requestId : requestId,
                    reviewOpinion : result.review_opinion
                },
                success: function(res){
                    layer.close(loadingIndex);
                    if(res.code === 0){
                        var index = layer.alert("通过评审!", {
                            title: '提示'
                        }, function () {
                            var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
                            parent.layer.close(index); //再执行关闭
                        });
                    }else{
                        layer.msg("评审失败!");
                    }
                },
                error: function(res){}
            });
        });
        return false;
    });

    //监听评审驳回按钮
    form.on('submit(noPassBtn)', function (data) {
        var result = data.field;
        layer.confirm('确定驳回？', {icon: 3, title:'提示'}, function(index){
            layer.close(index);
            var loadingIndex = layer.load(2);
            $.ajax({
                url: "/office_automation/public/index.php/index/document_c/noPassRequest",
                type:'post',
                data: {
                    requestId : requestId,
                    reviewOpinion : result.review_opinion
                },
                success: function(res){
                    layer.close(loadingIndex);
                    if(res.code === 0){
                        var index = layer.alert("已驳回申请!", {
                            title: '提示'
                        }, function () {
                            var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
                            parent.layer.close(index); //再执行关闭
                        });
                    }else{
                        layer.msg("驳回失败!");
                    }
                },
                error: function(res){}
            });
        });
        return false;
    });

    //监听返回按钮
    $("#back").on("click",function(){
        var index = parent.layer.getFrameIndex(window.name); //先得到当前iframe层的索引
        parent.layer.close(index); //再执行关闭
    });
});