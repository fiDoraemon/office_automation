layui.use(['form', 'table'], function () {
    var $ = layui.jquery,
        form = layui.form,
        table = layui.table;

    var isDocAdmin = false;  //标识是否是文控

    /**
     * 获取所需要的查询条件信息
     */
    $.ajax({
        url: "/office_automation/public/index.php/index/document_c/getProCOdeAndAuthor",
        type:'get',
        success: function(res){
            var projectCodes = res.data.projectCodes;
            var authors = res.data.authors;
            var $allStr = "<option value=\"\" selected>所有</option>";
            var $projectCodes = "<option value=\"0\" selected>所有</option>";
            var $projectStages = $allStr;
            var $authors = $allStr;
            isDocAdmin =  res.data.isDocAdmin;
            for (var i = 0; i < projectCodes.length; i++){
                $projectCodes += "<option value='" + projectCodes[i]["project_id"] + "'>" + projectCodes[i]["project_code"] + "</option>";
            }
            for (var i = 0; i < authors.length; i++){
                $authors += "<option value='" + authors[i]["author_id"] + "'>" + authors[i]["author_name"] + "</option>";
            }
            $("#projectSelect").append($projectCodes);
            $("#stageSelect").append($projectStages);
            $("#authorSelect").append($authors);
            tableRender();   //防止出现文控没有下载权限情况
            form.render('select');
        },
        error: function(res){
        }
    });

    function tableRender(){
        table.render({
            elem: '#fileList',
            url: '/office_automation/public/index.php/index/document_c/getAllDocFile',
            toolbar: '#toolbar',
            defaultToolbar: ['filter', 'exports', 'print', {
                title:    '提示',
                layEvent: 'LAYTABLE_TIPS',
                icon:     'layui-icon-tips'
            }],
            cols: [[
                {title: ' '  , width:50 ,
                    templet: function(d){
                        return `<i class="layui-icon layui-icon-file-b"></i>`;
                    }
                },
                {field: 'file_code',  title: '文档编码', width:180},
                {field: 'source_name',title: '文件名' ,
                    templet: function(d){
                        if(isDocAdmin){
                            return `<a style="color:#009688" href="/Office_Automation/public/upload/${d.path}" download="${d.file_code} ${d.source_name}">
                                        <i class="layui-icon layui-icon-download-circle"></i> ${d.source_name}
                                    </a>`;
                        }
                        return d.source_name;
                    }
                },
                {field: 'remark',     title: '文档说明'},
                {field: 'author',     title: '上传者',   width: 100},
                {field: 'project',    title: '所属项目', width: 100},
                {field: 'stage',      title: '项目阶段', width: 100},
                {field: 'upload_time',title: '归档日期', width: 200, sort: true},
                {title: '操作',       width:100,         align: "center",
                    templet: function(d){
                        if(isDocAdmin){
                            return `<a class="layui-btn layui-btn-xs"  href="/Office_Automation/public/upload/${d.path}" download="${d.file_code} ${d.source_name}">下载</a></a>`;
                        }
                        return "无权下载";
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

    /**
     * 监听所属项目复选框选择
     */
    form.on('select(projectCode)', function (data) {
        table.reload('fileList', {
            url: '/office_automation/public/index.php/index/document_c/getDocFileOfCondition',
            page: {
                curr: 1
            }
            , where: {
                projectCode: data.value,
                projectStage: $("#stageSelect").val(),
                author: $("#authorSelect").val()
            }
        }, 'data');
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
            },
            error: function (res) {
            }
        });
        return false;
    });

    /**
     * 监听项目阶段复选框选择
     */
    form.on('select(projectStage)', function (data) {
        table.reload('fileList', {
            url: '/office_automation/public/index.php/index/document_c/getDocFileOfCondition',
            page: {
                curr: 1
            }
            , where: {
                projectCode: $("#projectSelect").val(),
                projectStage: data.value,
                author: $("#authorSelect").val()
            }
        }, 'data');
    });

    /**
     * 监听作者复选框选择
     */
    form.on('select(author)', function (data) {
        table.reload('fileList', {
            url: '/office_automation/public/index.php/index/document_c/getDocFileOfCondition',
            page: {
                curr: 1
            }
            , where: {
                projectCode: $("#projectSelect").val(),
                projectStage: $("#stageSelect").val(),
                author: data.value
            }
        }, 'data');
    });

    /**
     * 搜索框监听操作
     */
    form.on('submit(data-search-btn)', function (data) {
        var result = data.field;
        table.reload('fileList', {
            url: '/office_automation/public/index.php/index/document_c/getDocFileOfKeyword',
            page: {
                curr: 1
            }
            , where: {
                keyword: result.keyword.trim(),
            }
        }, 'data');
        return false;
    });

});