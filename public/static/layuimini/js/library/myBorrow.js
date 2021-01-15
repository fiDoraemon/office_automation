layui.use(['table','form'],function () {
    var table = layui.table,
        form = layui.form,
        table = layui.table,
        $ = layui.jquery;
//执行渲染
    table.render({
        elem: '#borrowBook' //指定原始表格元素选择器（推荐id选择器）
        , url: '/office_automation/public/index.php/index/library_c/getMyBorrow'
        , height: 600 //容器高度
        , page: true
        , limit: 15
        , toolbar: '#top'
        , cols: [[
            {field: 'id',title: "序号",type:"numbers"},
            {field: 'book_id', title: "图书编号"},
            {field: 'name', title: "图书名称",},
            {field: 'start_time', title: "借阅开始时间",},
            {field: 'end_time',title: "归还时间"},
            {field: 'note', title: "备注",},
            {title: '操作', align: 'center', toolbar: '#borrowEnd'},
        ]]
    })

    table.on('tool(borrowFilter)',function (obj){
        var data = obj.data;
        var event = obj.event;
        if (event === "sendBack"){
            layer.open({
                type:0
                ,
            })
            $.ajax({
                url:"/office_automation/public/index.php/index/library_c/sendBook"
                ,type: "POST"
                ,data:{
                    data
                },
                success (res){
                    if (res["code"] === 0){
                        alert("归还成功");
                        table.reload('borrowBook'),{
                            url:"/office_automation/public/index.php/index/library_c/getMyBorrow"
                            ,page:{
                                curr:1
                            }
                        }
                    }else {
                        alert(res['msg']);
                    }
                }
            })
        }

    })
})