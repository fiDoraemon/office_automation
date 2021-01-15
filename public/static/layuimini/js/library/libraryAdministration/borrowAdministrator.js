layui.use(["table"],function (){
    var table = layui.table,
        $     = layui.jquery;

    table.render({
        elem:"#borrow_book"
        ,url:"/office_automation/public/index.php/index/library_c/getAllUserBorrow"
        ,page:true
        ,cols:[[
            {field:"id",                 title:"序号",         type:"numbers"},
            {field: "name",              title: "图书名称",     align:"center"},
            {field: "book_id",           title: "图书编号",     align:"center"} ,
            {field: "user_name",         title: "借阅人",       align:"center"},
            {field: "borrower_id",       title: "借阅人工号",    align:"center"},
            {field: "start_time",        title: "借阅开始时间",  align:"center"},
            {field: "end_time",          title: "借阅结束时间",  align:"center"},
            {field: "note",              title: "备注",         align:"center"},
            {field: "doThing",           title: "操作",         align:"center",           toolbar:"#borrow"}

        ]]
    })
})