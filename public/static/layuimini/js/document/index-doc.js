layui.use(['form', 'table'], function () {
    $ = layui.jquery,
    form = layui.form,
    table = layui.table;

    var isDocAdmin = false;  //标识是否是文控
    // 获取所需要的查询条件信息
    $.ajax({
        url: "/office_automation/public/index.php/index/document_c/getProCOdeAndAuthor",
        type: 'get',
        success: function (res) {
            var projectCodes = res.data.projectCodes;
            var authors = res.data.authors;
            var $allStr = "<option value=\"\" selected>所有</option>";
            var $projectCodes = "<option value=\"0\" selected>所有</option>";
            var $projectStages = $allStr;
            var $authors = $allStr;
            isDocAdmin = res.data.isDocAdmin;
            for (var i = 0; i < projectCodes.length; i++) {
                $projectCodes += "<option value='" + projectCodes[i]["project_id"] + "'>" + projectCodes[i]["project_code"] + "</option>";
            }
            for (var i = 0; i < authors.length; i++) {
                $authors += "<option value='" + authors[i]["applicant_id"] + "'>" + authors[i]["applicant_name"] + "</option>";
            }
            $("#projectSelect").append($projectCodes);
            $("#stageSelect").append($projectStages);
            $("#authorSelect").append($authors);
            tableRender();   //防止出现文控没有下载权限情况
            form.render('select');
        }
    });

    // 表格渲染
    function tableRender(){
        table.render({
            elem: '#fileList',
            url: '/office_automation/public/index.php/index/document_c/getAllDocFile',
            toolbar: '#toolbar',
            cols: [[
                {
                    title: '', width: '50', align: 'center',
                    templet: function (d) {
                        return `<i class="layui-icon layui-icon-file-b"></i>`;
                    }
                },
                {field: 'file_code', title: '文档编码', width: '180'},
                {
                    field: 'source_name', title: '文件名', width: '180',
                    templet: function (d) {
                        return d.source_name;
                    }
                },
                {field: 'description', title: '文档说明', width: '200'},
                {field: 'uploader', title: '上传者', width: '100', align: 'center'},
                {field: 'project_code', title: '所属项目', width: '100', align: 'center'},
                {field: 'project_stage', title: '项目阶段', width: '100', align: 'center'},
                {field: 'create_time', title: '归档时间', width: '180', sort: true, align: 'center'},
                {
                    field: 'version', title: '版本号', width: '100', align: 'center',
                    templet: function (d) {
                        return d.version + '.0';
                    }
                },
                {
                    title: '操作', minWidth: '200', align: 'center',
                    templet: function (d) {
                        let item = '<a class="layui-btn layui-btn-xs layui-btn-normal" lay-event="upgrade">升版</a>' +
                            '<a class="layui-btn layui-btn-primary layui-btn-xs" lay-event="history-version">历史版本</a>';
                        return item;
                    }
                }
            ]],
            id: 'fileList',
            limits: [10, 15, 20, 25, 50, 100],
            limit: 15,
            page: true,
            skin: 'line'
        });
    }

    // 监听所属项目复选框选择
    var projectCode;
    form.on('select(projectCode)', function (data) {
        if(data.value != projectCode) {
            // 重载表格
            table.reload('fileList', {
                page: {
                    curr: 1
                }
                , where: {
                    projectCode: data.value,
                    projectStage: $("#stageSelect").val(),
                    uploader: $("#authorSelect").val()
                }
            }, 'data');
            // 重置项目阶段
            $("#stageSelect").empty();
            $.ajax({
                url: "/office_automation/public/index.php/index/document_c/getProjectStage",
                type: 'get',
                data: {projectId: data.value},
                success: function (res) {
                    var projectStages = res;
                    var $projectStages = "<option value=\"\" selected>所有</option>";
                    for (var i = 0; i < projectStages.length; i++) {
                        $projectStages += "<option value='" + projectStages[i] + "'>" + projectStages[i] + "</option>";
                    }
                    $("#stageSelect").append($projectStages);
                    //需要重新加载
                    form.render('select');
                }
            });
            projectCode = data.value;
        }
        return false;
    });

    // 监听项目阶段下拉选择选择
    form.on('select(projectStage)', function (data) {
        table.reload('fileList', {
            page: {
                curr: 1
            }
            , where: {
                projectCode: $("#projectSelect").val(),
                projectStage: data.value,
                uploader: $("#authorSelect").val()
            }
        }, 'data');
    });

    // 监听上传者下拉选择
    form.on('select(uploader)', function (data) {
        table.reload('fileList', {
            page: {
                curr: 1
            }
            , where: {
                projectCode: $("#projectSelect").val(),
                projectStage: $("#stageSelect").val(),
                uploader: data.value
            }
        }, 'data');
    });

    // 搜索框监听操作
    form.on('submit(data-search-btn)', function (data) {
        var result = data.field;
        table.reload('fileList', {
            page: {
                curr: 1
            }
            , where: {
                keyword: result.keyword.trim(),
            }
        }, 'data');
        return false;
    });

    // 我发起的会议按钮监听
    // table.on('toolbar(fileListFilter)', function (obj) {
    //     if (obj.event === 'borrow') {
    //         table.reload('fileList', {
    //             url: '/office_automation/public/index.php/index/document_c/getMyBorrow',
    //         }, 'data');
    //         return false;
    //     }else if(obj.event === 'approval'){
    //         if(!isDocAdmin){
    //             layer.msg("您没有审批权限！");
    //             return;
    //         }
    //         var index = layer.open({
    //             title: '待审批文档借阅',
    //             type: 2,
    //             shade: 0.2,
    //             maxmin:true,
    //             shadeClose: true,
    //             area: ['100%', '100%'],
    //             content: 'approval-doc.html',
    //         });
    //         $(window).on("resize", function () {
    //             layer.full(index);
    //         });
    //         return false;
    //     }
    // });

    // 监听文档表格操作按钮
    var fileId;
    table.on('tool(fileListFilter)', function (obj) {
        let data = obj.data;
        if (obj.event == 'upgrade') {
            index = layer.open({
                title: '文档升版',
                type: 2,
                shade: 0.2,
                maxmin: true,
                shadeClose: true,
                area: ['100%', '100%'],
                content: 'upgrade-doc.html?fileId=' + data.file_id,
            });
        } else if (obj.event == 'history-version') {
            fileId = data.file_id;
            // 获取文档其它版本
            $.get(
                '/office_automation/public/index/document_c/getFileVersion?fileId=' + data.file_id,
                function(res){
                    var fileVersionList = res.data.fileVersionList;
                    var fileCode = res.data.fileCode;
                    // var element = $(`
                    // <table class="layui-table">
                    //     <colgroup>
                    //       <col width="10%">
                    //       <col width="10%">
                    //       <col width="30%">
                    //       <col width="30%">
                    //       <col width="20%">
                    //     </colgroup>
                    //     <thead>
                    //       <tr>
                    //         <th>版本号</th>
                    //         <th>上传者</th>
                    //         <th>文件名</th>
                    //         <th>升版说明</th>
                    //         <th>操作</th>
                    //       </tr>
                    //     </thead>
                    //     <tbody></tbody>
                    // </table>`);
                    // for(var i in fileVersionList) {
                    //     element.find('tbody').append(`
                    //     <tr>
                    //         <td>${fileVersionList[i].version}</td>
                    //         <td>${fileVersionList[i].uploader}</td>
                    //         <td>${fileVersionList[i].attachment.source_name}</td>
                    //         <td>${fileVersionList[i].description}</td>
                    //         <td><button type="button" class="layui-btn layui-btn-normal layui-btn-xs">下载</button></td>
                    //     </tr>`);
                    // }
                    var element = `
                    <div style="padding: 20px 20px">
                        <table class="layui-hide" id="version-list" lay-filter="version-list"></table>
                    </div>`;
                    layer.open({
                        type: 1,
                        title: '历史版本',
                        content: element,
                        skin: 'layui-layer-molv',
                        area: ['70%', '80%'],
                        shadeClose: true,
                        anim: 1
                    });
                    table.render({
                        elem: '#version-list',
                        cols: [[
                            {field: 'version', title: '版本号', width: '10%', align: 'center',
                                templet: function (d) {
                                    return d.version + '.0';
                                }
                            },
                            {field: 'uploader',title: '上传者', width: '15%', align: 'center'},
                            {field: 'source_name', title: '文件名', width: '20%',
                                templet: function (d) {
                                    var attachment = d.attachment;
                                    if(isDocAdmin || d.isUploader || d.isBorrow) {
                                        return `
                                        <a style="color:#009688" href="/Office_Automation/public/upload/${attachment.save_path}" download="${fileCode}-受控-${attachment.source_name}">
                                            <i class="layui-icon layui-icon-download-circle"></i> ${attachment.source_name}
                                        </a>`;
                                    } else {
                                        return d.attachment.source_name;
                                    }
                                }
                            },
                            {field: 'description', title: '升版说明', width: '20%'},
                            {field: 'create_time', title: '上传时间', width: '20%', align: 'center'},
                            {title: '操作', width: '15%', align: 'center',
                                templet: function(d) {
                                    var attachment = d.attachment;
                                    if(isDocAdmin || d.isUploader || d.isBorrow) {
                                        return `<a class="layui-btn layui-btn-xs" href="/Office_Automation/public/upload/${attachment.save_path}" download="${fileCode}-受控-${attachment.source_name}">下载</a>`;
                                    } else {
                                        return  '<a class="layui-btn layui-btn-xs" lay-event="borrow">借阅</a>';
                                    }

                                }
                            }
                        ]],
                        id: 'version-list',
                        skin: 'line',
                        data: fileVersionList
                    });
                }
            );
        }
    });

    // 监听版本表格操作按钮
    table.on('tool(version-list)', function (obj) {
        var data = obj.data;
        console.log(data);
        if (obj.event == 'borrow') {
            layer.confirm('确定借阅？', {icon: 3, title: '提示'}, function (index) {
                layer.close(index);
                $.ajax({
                    url: "/office_automation/public/index.php/index/document_c/saveRequest",
                    type: 'post',
                    data: {
                        fileId: fileId,
                        version: data.version
                    },
                    success: function (res) {
                        if (res.code == 0) {
                            layer.msg('申请成功，请等待文控审批', {icon: 6});
                        } else {
                            layer.msg(res.msg);
                        }
                    }
                });
            });
        }
        return false;
    });
});