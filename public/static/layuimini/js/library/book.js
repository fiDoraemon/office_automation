var table = layui.table;
//执行渲染
table.render({
    elem: '#book' //指定原始表格元素选择器（推荐id选择器）
    , url: '/office_automation/public/index.php/index/library_c/getBoos'
    , height: 315 //容器高度
    , page: true
    , toolbar: '#top'
    , cols: [[
        {file: 'selects', title: "多选", tpye: "checkbox"},
        {file: 'bookId', title: "图书编号",},
        {file: 'name', title: "图书名称",},
        {file: 'publisher', title: "出版社",},
        {file: 'classification', title: "图书分类",},
        {file: 'introduction', title: "图书分类",},
    ]]
})

//监听事件
    table.on('toolbar(test)', function (obj) {
        var checkStatus = table.checkStatus(obj.config.id);
        switch (obj.event) {
            case 'add':
                layer.msg('添加');
                break;
            case 'search':
                layer.msg('删除');
                break;
            case 'update':
                layer.msg('编辑');
                break;
        }
    })

