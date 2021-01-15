layui.use(['table','form'],function (){
    var form  = layui.form,
        $     = layui.jquery;
    queryClassification(form);

    $("#add").on("click",function (){
        layer.prompt({
            formType: 0,
            value: '',
            title: '请输入新的分类',
            area: ['800px', '350px'] //自定义文本域宽高
        }, function(value, index, elem){
            $.ajax({
                url:"/office_automation/public/index/library_c/addClassification"
                ,data: {
                    value
                },
                type:"post"
                ,success(res){
                    if (res['code'] !== 0){
                        alert("添加失败");
                    }else {
                        alert("添加成功");
                    }
                    queryClassification(form);
                    form.render("select");
                }
            })
            layer.close(index);
        });
    })

    form.on("submit(add-book-data)",function (data){
        var addBook = data.field;
        /*var book = addBook['book'];
        var publisher = addBook['publisher'];
        var select = addBook['select'];
        var status = addBook['status'];
        var desc = addBook['desc'];
        alert(book);
        alert(publisher);
        alert(select);
        alert(status);
        alert(desc);*/
        $.ajax({
            url:"/office_automation/public/index/library_c/addBook"
            ,type:"POST"
            ,data:{
                addBook
            }
            ,success:function (res){
                if (res['code'] === 0){
                    alert("添加图书成功");
                }
                if (res['code'] === 1){
                    alert("添加失败,请重试!");
                }
            }
        })

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
                var option = "<option value='" + classifition[i]['id'] + "'>" + classifition[i]['classification'] + "</option>";
                $("#classification_select").append(option);
            }
            //需要重新加载
            form.render('select');
        },
        error: function(data){
        }
    })
}