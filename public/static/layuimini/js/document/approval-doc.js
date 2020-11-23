table.render({
    elem: '#fileList',
    url: '/office_automation/public/index.php/index/document_c/getAllApproval',
    cols: [[
        {field: 'user_name',   title: '申请人'},
        {field: 'code',        title: '文档编码',   width: 100},
        {field: 'name',        title: '文件名', width: 100},
        {field: 'request_time',title: '申请日期', width: 200, sort: true},
        {title: '操作',        width:100,         align: "center"}
    ]],
    id: 'fileList',
    limits: [10, 15, 20, 25, 50, 100],
    limit: 15,
    page: true,
    skin: 'line'
});