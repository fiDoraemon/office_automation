layui.use(['form', 'table'], function () {
    var $ = layui.jquery,
        form = layui.form,
        table = layui.table;

    table.render({
        elem: '#tableList',
        url: '/office_automation/public/index.php/index/admin_c/getWorkTable',
        defaultToolbar: ['filter', 'exports', 'print', {
            title:    '提示',
            layEvent: 'LAYTABLE_TIPS',
            icon:     'layui-icon-tips'
        }],
        cols: [[
            {field: 'table_name',  title: '工作表名称'},
            {field: 'creator_name',title: '创建人'},
            {field: 'description', title: '表描述'},
            {field: 'create_time', title: '创建时间日期', sort: true},
            {field: 'status',      title: '状态',
                templet: function(d){
                    if(d.status === 1){
                        return "<i style='color: #009688' class='layui-icon layui-icon-ok'>&emsp;使用中</i>";
                    }
                    return "<i style='color: #FF5722' class='layui-icon layui-icon-close'>&emsp;已停用</i>";
                }
            },
            {title: '操作', toolbar: '#requestBar', align: "center"}
        ]],
        id: 'tableList',
        limits: [10, 15, 20, 25, 50, 100],
        limit: 15,
        page: true,
        skin: 'line'
    });

    /**
     * 搜索框监听操作
     */
    form.on('submit(data-search-btn)', function (data) {
        var result = data.field;
        table.reload('tableList', {
            url: '/office_automation/public/index.php/index/admin_c/getWorkTable',
            page: {
                curr: 1
            }
            , where: {
                keyword: result.keyword.trim(),
            }
        }, 'data');
        return false;
    });

    table.on('tool(tableListFilter)', function (obj) {
        var data = obj.data;
        var tableId = data["table_id"];
        if (obj.event === 'edit') {
            var index = layer.open({
                title: '工作表结构#('+ tableId +')',
                type: 2,
                shade: 0.2,
                maxmin:true,
                shadeClose: true,
                area: ['100%', '100%'],
                content: 'update-admin.html?tableId=' + tableId,
            });
            $(window).on("resize", function () {
                layer.full(index);
            });
            return false;
        }
    });

});