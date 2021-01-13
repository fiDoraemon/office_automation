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
            {field: 'id', title: "序号"},
            {field: 'book_id', title: "图书编号"},
            {field: 'name', title: "图书名称",},
            {field: 'start_time', title: "借阅开始时间",},
            {field: 'note', title: "备注",},
            {title: '操作', align: 'center', toolbar: '#borrowEnd'},
        ]]

    })
})