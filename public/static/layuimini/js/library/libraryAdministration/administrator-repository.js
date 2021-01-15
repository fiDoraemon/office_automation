layui.use(['table','form'],function (){
    var table   = layui.table,
        form    = layui.form,
        $       = layui.jquery ;
    queryClassification(form);
//执行渲染
    table.render({
        elem: '#book' //指定原始表格元素选择器（推荐id选择器）
        , url: '/office_automation/public/index.php/index/library_c/getAllBooks'
        , height: 600 //容器高度
        , page: true
        , limit:15
        , toolbar: '#add'
        , cols: [[
            /*{field: 'longSelect',        title: "多选",        type:'checkbox'},*/
            {field: 'id',                title: "序号",       type:"numbers"},
            {field: 'book_id',           title: "书本编号"},
            {field: 'name',              title: "图书名称",},
            {field: 'publisher',         title: "出版社",},
            {field: 'borrow_status',     title: "借阅状态",},
            {field: 'classification',    title: "图书分类",},
            {title: '操作',               align:'center',    toolbar:'#edit'},
        ]]
    })
    form.on("submit(data-search-btn)",function (data){
        var result = data.field;
        table.reload("fileList",{
            page: {
                curr:1
            }
            ,   where : {
                data:result
            }
        },'data')
        return false;
    })

    table.on("tool(fileListFilter)",function (obj){
        var data = obj.data;
        var event = obj.event;
        if (event === 'borrow'){
            $.ajax({
                url:"/office_automation/public/index.php/index/library_c/lendBook"
                ,type:"POST"
                ,data:{
                    data
                }
                ,success: function(res) {

                }
            })
        }
        return false;
    })
})
function queryClassification(form){
    $.ajax({
        url: "/office_automation/public/index.php/index/library_c/getClassification",
        type:'post',
        data: {
        },
        success: function(res){
            var classifition = res["data"];
            for (var i = 0; i < classifition.length; i++){
                var $option = "<option value='" + classifition[i]['id'] + "'>" + classifition[i]['classification'] + "</option>";
                $("#classification_select").append($option);
            }
            //需要重新加载
            form.render('select');
        }
    })
}



