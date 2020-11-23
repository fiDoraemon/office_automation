layui.use(['form', 'table'], function () {
    var     $ = layui.jquery,
         form = layui.form,
        table = layui.table;

    table.render({
        elem: '#fileList',
        url: '/office_automation/public/index.php/index/document_c/getAllApproval',
        cols: [[
            {field: 'user_name',   title: '申请人'},
            {field: 'code',        title: '文档编码'},
            {field: 'name',        title: '文件名'},
            {field: 'request_time',title: '申请日期', sort: true},
            {title: '操作',        toolbar: '#currentTableBar', align: "center"}
        ]],
        id: 'fileList',
        limits: [10, 15, 20, 25, 50, 100],
        limit: 15,
        page: true,
        skin: 'line'
    });

    table.on('tool(fileListFilter)', function (obj) {
        var data = obj.data;
        var borrowId = data["id"];
        if (obj.event === 'pass') {
            layer.confirm('确定同意？', {icon: 3, title:'提示'}, function(index){
                $.ajax({
                    type: 'post',
                    url: "/office_automation/public/index.php/index/document_c/passBorrow",
                    data:{borrowId:borrowId},
                    success: function(res){
                        if(res.code === 0){
                            layer.msg("已同意该借阅申请！");
                        }else{
                            layer.msg("操作失败！");
                        }
                        window.location.reload();
                    } ,
                    error: function(res){}
                });
            });
            return false;
        }else if(obj.event === 'no-pass'){
            layer.confirm('确定反对？', {icon: 3, title:'提示'}, function(index){
                $.ajax({
                    type: 'post',
                    url: "/office_automation/public/index.php/index/document_c/noPassBorrow",
                    data:{borrowId:borrowId},
                    success: function(res){
                        if(res.code === 0){
                            layer.msg("已反对该借阅申请！");
                        }else{
                            layer.msg("操作失败！");
                        }
                        window.location.reload();
                    } ,
                    error: function(res){

                    }
                });
            });
            return false;
        }
    });
});

