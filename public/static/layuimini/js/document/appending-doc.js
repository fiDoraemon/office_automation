layui.use(['form', 'table'], function () {
    var $ = layui.jquery,
        form = layui.form,
        table = layui.table;

    /**
     * 获取所需要的查询条件信息
     */
    $.ajax({
        url: "/office_automation/public/index.php/index/document_c/getProjectCode",
        type:'get',
        success: function(res){
            var projectCodes = res;
            var $projectCodes = "";
            for (var i = 0; i < projectCodes.length; i++){
                $projectCodes += "<option value='" + projectCodes[i]["project_id"] + "'>" + projectCodes[i]["project_code"] + "</option>";
            }
            $("#projectSelect").append($projectCodes);
            //需要重新加载
            form.render('select');
        },
        error: function(res){
        }
    });

    table.render({
        elem: '#requestList',
        url: '/office_automation/public/index.php/index/document_c/getAllRequest',
        toolbar: '#toolbar',
        defaultToolbar: ['filter', 'exports', 'print', {
            title:    '提示',
            layEvent: 'LAYTABLE_TIPS',
            icon:     'layui-icon-tips'
        }],
        cols: [[
            {field: 'request_id',    title:   '编号'},
            {field: 'author_name',   title:   '申请人'},
            {field: 'approver_name', title:   '审批人'},
            {field: 'project_code',  title:   '所属项目'},
            {field: 'stage',         title:   '项目阶段'},
            {field: 'remark',        title:   '文档描述'},
            {field: 'request_time',  title:   '发起日期', sort: true},
            {field: 'status',        title:   '评审状态',
                templet: function(d){
                    switch (d.status) {
                        case 0 :
                            return `<span style="color:#01AAED" >待审批</span>`;break;
                        case 1 :
                            return `<span style="color:#009688" >已通过</span>`;break;
                        case -1:
                            return `<span style="color:#FF5722" >已驳回</span>`;break;
                    }
                }
            },
            {title: '操作', toolbar: '#requestBar', align: "center"}
        ]],
        id: 'requestList',
        limits: [10, 15, 20, 25, 50, 100],
        limit: 15,
        page: true,
        skin: 'line'
    });

    table.on('tool(currentTableFilter)', function (obj) {
        var data = obj.data;
        var requestId = data["request_id"];
        if (obj.event === 'edit') {
            var index = layer.open({
                title: '申请详情#('+ requestId +')',
                type: 2,
                shade: 0.2,
                maxmin:true,
                shadeClose: true,
                area: ['100%', '100%'],
                content: 'review-doc.html?requestId=' + requestId,
            });
            $(window).on("resize", function () {
                layer.full(index);
            });
            return false;
        }
    });

    /**
     * 搜索框监听操作
     */
    form.on('submit(data-search-btn)', function (data) {
        var result = data.field;
        //执行搜索重载
        table.reload('requestList', {
            url: '/office_automation/public/index.php/index/document_c/getRequestOfKeyword',
            page: {
                curr: 1
            }
            , where: {
                keyword: result.keyword,
            }
        }, 'data');
        return false;
    });

    /**
     * 监听项目代号查询
     */
    form.on('select(projectCode)',function(data){
        table.reload('requestList', {
            url: '/office_automation/public/index.php/index/document_c/getRequestOfProject',
            page: {
                curr: 1
            }
            , where: {
                projectCode: data.value,
                projectStage: $("#stageSelect").val(),
            }
        }, 'data');
        $("#stageSelect").empty();
        $.ajax({
            url: "/office_automation/public/index.php/index/document_c/getProjectStage",
            type:'get',
            data:{ projectId : data.value},
            success: function(res){
                var projectStages = res;
                var $projectStages = "<option value=\"\" selected>所有</option>";
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

    /**
     * 监听项目阶段查询
     */
    form.on('select(projectStage)',function(data){
        table.reload('requestList', {
            url: '/office_automation/public/index.php/index/document_c/getRequestOfProject',
            page: {
                curr: 1
            }
            , where: {
                projectCode: $("#projectSelect").val(),
                projectStage: data.value,
            }
        }, 'data');
        return false;
    });

    /**
     * 待处理申请按钮监听
     */
    table.on('toolbar(currentTableFilter)', function (obj) {
        if (obj.event === 'my-request') {  // 监听添加操作
            table.reload('requestList', {
                url: '/office_automation/public/index.php/index/document_c/getRequestOfMyRequest',
                page: {
                    curr: 1
                }
            }, 'data');
            return false;
        } else if (obj.event === 'my-review') {  // 监听删除操作
            table.reload('requestList', {
                url: '/office_automation/public/index.php/index/document_c/getRequestOfMyReview',
                page: {
                    curr: 1
                }
            }, 'data');
            return false;
        } else if(obj.event === 'refresh'){
            location.reload();
        }
    });

});