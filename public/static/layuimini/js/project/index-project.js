layui.use(['form', 'table'], function () {
    var $ = layui.jquery,
        form = layui.form,
        miniTab = layui.miniTab,
        table = layui.table;

    table.render({
        elem: '#fileList',
        url: '/office_automation/public/index.php/index/admin_c/getAllProject',
        cols: [[
            {field: 'project_id',   title: '项目ID'  ,width: 80, align: 'center'},
            {field: 'project_code', title: '项目代号'},
            {field: 'project_name', title: '项目名称'},
            {field: 'description',  title: '描述'},
            {field: 'doc_stage',    title: '包含阶段'},
            {title: '操作',         align: 'center', toolbar: '#currentTableBar' }
        ]],
        id: 'fileList',
        page: false,
        // skin: 'line'
    });

    /**
     * 搜索框监听操作
     */
    // form.on('submit(data-search-btn)', function (data) {
    //     var result = data.field;
    //     table.reload('fileList', {
    //         url: '/office_automation/public/index.php/index/document_c/getDocFileOfKeyword',
    //         page: {
    //             curr: 1
    //         }
    //         , where: {
    //             keyword: result.keyword.trim(),
    //         }
    //     }, 'data');
    //     return false;
    // });

    $("#add").on("click",function(){
         var index = layer.open({
            title: '增加项目',
            type: 2,
            shade: 0.2,
            maxmin:true,
            shadeClose: true,
            area: ['100%', '100%'],
            content: 'add-project.html',
        });
        $(window).on("resize", function () {
            layer.full(index);
        });
    });

    table.on('tool(currentTableFilter)', function (obj) {
        var data = obj.data;
        var projectId = data["project_id"];
        if (obj.event === 'edit') {
            var index = layer.open({
                title: '修改项目',
                type: 2,
                shade: 0.2,
                maxmin:true,
                shadeClose: true,
                area: ['100%', '100%'],
                content: 'update-project.html?projectId=' + projectId,
            });
            $(window).on("resize", function () {
                layer.full(index);
            });
            return false;
        }
    });

});