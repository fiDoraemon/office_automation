layui.use(['form','miniTab','upload'], function () {
    var $ = layui.jquery,
        form = layui.form,
        layer = layui.layer,
        upload = layui.upload,
        miniTab = layui.miniTab;

    var uploadList = []; //上传的文件

    /**
     * 获取所需要的查询条件信息
     */
    $.ajax({
        url: "/office_automation/public/index.php/index/document_c/getProCodeAndReviewer",
        type:'get',
        success: function(res){
            var projectCodes = res.data.projectCodes;
            var reviewers = res.data.reviewer;
            var $projectCodes = "";
            var $reviewers = "";
            for (var i = 0; i < projectCodes.length; i++){
                $projectCodes += "<option value='" + projectCodes[i]["project_id"] + "'>" + projectCodes[i]["project_code"] + "</option>";
            }
            for (var i = 0; i < reviewers.length; i++){
                $reviewers += "<option value='" + reviewers[i]["user_id"] + "'>" + reviewers[i]["user_name"] + "</option>";
            }
            $("#projectSelect").append($projectCodes);
            $("#reviewerSelect").append($reviewers);
            //需要重新加载
            form.render('select');
        },
        error: function(res){
        }
    });

    //监听选择所属项目的下拉选择
    form.on('select(selectCode)',function(data){
        $("#stageSelect").empty();
        $.ajax({
            url: "/office_automation/public/index.php/index/document_c/getProjectStage",
            type:'get',
            data:{ projectId : data.value},
            success: function(res){
                var projectStages = res;
                var $projectStages = "<option value=\"\">--请选择--</option>";
                for (var i = 0; i < projectStages.length; i++){
                    $projectStages += "<option value='" + projectStages[i] + "'>" + projectStages[i] + "</option>";
                }
                $("#stageSelect").append($projectStages);
                //需要重新加载
                form.render('select');
            },
            error: function(res){
            }
        });
        return false;
    });

    //监听选择所属项目前缀下拉选择
    form.on('select(selectStagePre)',function(data){
        $("#stageFix").empty();
        $.ajax({
            url: "/office_automation/public/index.php/index/document_c/getProjectStageFix",
            type:'get',
            data:{ stagePre : data.value},
            success: function(res){
                console.log("res",res);
                var $projectStages = "<option value=\"0\">--请选择--</option>";
                for (var i = 0; i < res.length; i++){
                    $projectStages += "<option value='" + res[i] + "'>" + res[i] + "</option>";
                }
                $("#stageFix").append($projectStages);
                //需要重新加载
                form.render('select');
            },
            error: function(res){
            }
        });
        return false;
    });

    //文件上传
    var fileListView = $('#fileList')
        ,uploadListIns = upload.render({
        elem: '#attachment'
        ,url: '/office_automation/public/attachment'
        ,auto: false
        ,multiple: true
        ,size: 51200            // 单位 KB，最大 50MB
        ,accept: 'file'
        ,bindAction: '#start_upload'
        ,choose: function(obj){
            if($('#fileList').children().length > 9) {
                return layer.msg('最多上传十个附件！');
            }
            var files = this.files = obj.pushFile();         // 将每次选择的文件追加到文件队列
            $("#start_upload").removeAttr("disabled");
            // 预读本地文件
            obj.preview(function(index, file, result){          // 分别是文件索引、文件对象、文件base64编码
                var tr = $(['<tr id="upload-'+ index +'">'
                    ,'<td>'+ file.name +'</td>'
                    ,'<td>'+ (file.size/1024).toFixed(1) +'kb</td>'
                    ,'<td>等待上传</td>'
                    ,'<td>'
                    ,'<button class="layui-btn layui-btn-xs layui-hide reload">重传</button>'
                    ,'<button class="layui-btn layui-btn-xs layui-btn-danger delete">删除</button>'
                    ,'</td>'
                    ,'</tr>'].join(''));

                //单个重传
                tr.find('.reload').on('click', function(){
                    obj.upload(index, file);
                });

                //删除
                tr.find('.delete').on('click', function(){
                    delete files[index]; //删除对应的文件
                    tr.remove();
                    uploadListIns.config.elem.next()[0].value = '';         // 清空 input document 值，以免删除后出现同名文件不可选
                    if($.isEmptyObject(files)) {
                        $("#start_upload").attr("disabled", true);
                    }
                });
                fileListView.append(tr);
            });
        }
        ,before: function(obj){
            layer.load();            // 取消上传后仍会触发 before
        }
        ,done: function(res, index, upload){
            if(res.code === 0){
                // 写入附件 id
                uploadList.push(res.data.id);
                var tr = fileListView.find('tr#upload-'+ index)
                    ,tds = tr.children();
                tds.eq(2).html('<span style="color: #5FB878;">上传成功</span>');
                tds.eq(3).html('');
                delete this.files[index];            // 删除文件队列已经上传成功的文件
            } else {
                console.log('上传失败：' + res.msg + `(${index})`);
                this.error(index, upload);
            }
        }
        ,allDone: function(obj){
            layer.closeAll('loading');
            layer.msg('上传完成！');
            $("#start_upload").attr("disabled", true);
            console.log('上传完成！共上传' + obj.total + '个文件，成功文件数：' + obj.successful +'，失败文件数：' + obj.aborted);
        }
        ,error: function(index, upload){            // 分别为当前文件的索引、重新上传的方法
            var tr = fileListView.find('tr#upload-'+ index)
                ,tds = tr.children();
            tds.eq(2).html('<span style="color: #FF5722;">上传失败</span>');
            tds.eq(3).find('.reload').removeClass('layui-hide');           // 显示重传
        }
    });


    //监听提交
    form.on('submit(save)', function (data) {
        if(uploadList.length === 0){
            layer.msg('未检测到有已上传文件！', {icon: 5});
            return false;
        }
        var result = data.field;
        layer.confirm('确定发起评审？', {icon: 3, title:'提示'}, function(index){
            layer.close(index);
            var loadingIndex = layer.load(2);
            let stageFix = result.project_stage_fix;
            let stage = result.project_stage;
            if(stageFix !== "0"){
                stage += ("-" + stageFix);
            }
            $.ajax({
                url: "/office_automation/public/index.php/index/document_c/saveRequest",
                type:'post',
                data: {
                    projectCode    : result.project_code,
                    projectStage   : stage,
                    approver       : result.approver,
                    remark         : result.remark,
                    uploadList     : uploadList
                },
                success: function(res){
                    layer.close(loadingIndex);
                    if(res.code === 0){
                        var index = layer.alert("发起成功", {
                            title: '提示'
                        }, function () {
                            layer.close(index);
                            location.reload();
                            miniTab.openNewTabByIframe({
                                href:"page/document/index-doc.html",
                                title:"文档搜索",
                            });
                        });
                    }else{
                        var index = layer.alert("发起失败！", {
                            title: '提示'
                        });
                    }
                },
                error: function(res){}
            });
        });
        return false;
    });
});