layui.use(['form', 'layedit', 'laydate' ,'upload','miniTab'], function () {
    var   form    = layui.form
        , layer   = layui.layer
        , miniTab = layui.miniTab
        , $       = layui.$;

    $('.sortablelist').clickSort();  //使用移动动画

    var userIdList = []; //已经选择的应到会议员工id数组
    var fieldCount = 1;  //当前已经有多少个字段

    //获取所有部门信息
    $.ajax({
        url:"/office_automation/public/index.php/index/user_c/getAllDepartment",
        type: "get",
        data:{},
        success: function(res){
            let departmentArray = res.data;
            let departmentInfo = "";
            for (var i = 0; i < departmentArray.length; i++){
                departmentInfo += "<option value='" + departmentArray[i]["department_id"] + "'>" + departmentArray[i]["department_name"] + "</option>";
            }
            $("#select-department").append(departmentInfo);
            form.render();
        },
        error: function(res){
        }
    });

    //选择部门
    form.on('select(select-department)', function (data) {
        $("#select-user").empty();
        $.ajax({
            url: "/office_automation/public/index.php/index/user_c/getUserOfDepartment",
            type: 'get',
            data: { departmentId: data.value },
            success: function (res) {
                let userArr = res.data;
                let $userList = "<option value='0'></option>";
                for (let i = 0; i < userArr.length; i++) {
                    $userList += "<option value='" + userArr[i].user_id + "'>" + userArr[i].user_name + "</option>";
                }
                $("#select-user").append($userList);
                //需要重新加载
                form.render('select');
            },
            error: function (res) {
            }
        });
        return false;
    });

    //选择用户
    form.on('select(select-user)', function (data) {
        let userName = data.elem[data.elem.selectedIndex].text;
        let userId = data.value;
        let userInfo = ' <a href="javascript:;" class="test">' +
            ' <span lay-value="' + userId + '">' + userName + '(' + userId + ')' + '</span>' +
            ' <i class="layui-icon layui-icon-close"></i>' +
            ' </a>';
        if ($.inArray(userId, userIdList) === -1) {
            userIdList.push(userId);
            $("#userList").append(userInfo);
        }
        var $element = $("#select-user .layui-form-selected dl");
        $element.addClass("test")
        return false;
    });

    var fieldValue = "<input type=\"text\" style=\"width:500px\" name=\"fieldValue\" lay-verify=\"required\"  placeholder=\"以中文逗号'，'隔开\" class=\"layui-input fieldValue\">";
    //选择字段类型监听
    form.on('select(select-field-type)',function(data){
        let fieldType = data.value;
        var thisDom = $(data.elem); //DOM对象转jq对象
        if(fieldType === "select"){
            thisDom.parent().next().next().next().html(fieldValue);
        }else{
            thisDom.parent().next().next().next().html("");
        }
    });

    //删除一行
    $("ul").on("click","li .field-btn .delete",function(){
        if(fieldCount < 2){
            layer.msg("表中必须有字段！");
        }else{
            $(this).hide({
                duration: 300,
                complete: function() {
                    fieldCount--;
                    $(this).parent().parent().remove();
                }
            })
        }
    });

    //添加一行
    $("ul").on("click","li .field-btn .add",function(){
        if(fieldCount < 25){
            fieldCount++;
            $(this).parent().parent().after(newField);
            form.render();
        }else{
            layer.msg("一个表最多只能25个字段！");
        }
    });

    //在数组中删除某一个元素
    var removeFromArray = function (arr, val) {
        var index = $.inArray(val, arr);
        if (index >= 0)
            arr.splice(index, 1);
        return arr;
    };

    //删除应到会人员
    $(".multiSelect").on("click", "i", function () {
        var userId = $(this).prev('span').attr("lay-value");
        removeFromArray(userIdList, userId);
        $(this).parent().remove();
    });

    var newField = " <li class=\"sortableitem\">\n" +
        "                                <div class=\"layui-input-inline\">\n" +
        "                                    <select name=\"fieldType\" style=\"float: left;\" class=\"fieldType\" lay-filter=\"select-field-type\" lay-verify=\"required\">\n" +
        "                                        <option value=\"\">--字段类型--</option>\n" +
        "                                        <option value=\"text\" selected>单行文本</option>\n" +
        "                                        <option value=\"textarea\">多行文本</option>\n" +
        "                                        <option value=\"select\">自定义多选</option>\n" +
        "                                        <option value=\"user\">单选员工</option>\n" +
        "                                        <option value=\"users\">多选员工</option>\n" +
        "                                        <option value=\"date\">日期</option>\n" +
        "                                    </select>\n" +
        "                                </div>\n" +
        "                                <div class=\"layui-input-inline\">\n" +
        "                                    <input type=\"text\" style=\"float: left;\" name=\"fieldName\" lay-verify=\"required\"  placeholder=\"请输入字段名\" class=\"layui-input fieldName\">\n" +
        "                                </div>\n" +
        "                                <div class=\"layui-input-inline field-btn\">\n" +
        "                                    <a class=\"layui-btn-xs data-count-edit icon-btn down movedown\" lay-event=\"down\"><i class=\"fa fa-arrow-down\" aria-hidden=\"true\"></i></a>\n" +
        "                                    <a class=\"layui-btn-xs data-count-edit icon-btn up moveup\" lay-event=\"up\"><i class=\"fa fa-arrow-up\" aria-hidden=\"true\"></i></a>\n" +
        "                                    <a class=\"layui-btn layui-btn-xs layui-btn-danger icon-btn delete\" lay-event=\"delete\"><i class=\"layui-icon layui-icon-subtraction\"></i></a>\n" +
        "                                    <a class=\"layui-btn layui-btn-xs data-count-edit icon-btn add\" lay-event=\"add\"><i class=\"layui-icon layui-icon-addition\"></i></a>\n" +
        "                                </div>\n" +
        "                                <div class=\"layui-input-inline field-value\">\n" +
        "                                </div>\n" +
        "                            </li>";

    function getFieldList(){
        var fieldInfos = [];
        $('ul li').each(function(){
            let fieldType    = $(this).find(".fieldType").val();
            let fieldName    = $(this).find(".fieldName").val();
            let fieldValue   = $(this).find(".fieldValue").val();
            let field = "";
            if(fieldType === "select"){
                field = {"fieldType":fieldType,"fieldName":fieldName,"fieldValue":fieldValue};
            }else{
                field = {"fieldType":fieldType,"fieldName":fieldName};
            }
            fieldInfos.push(field);
        });
        return fieldInfos;
    }

    //监听提交
    form.on('submit(save)', function (data) {
        var result = data.field;
        layer.confirm('确定生成工作表？', {icon: 3, title:'提示'}, function(index){
            layer.close(index);
            var loadingIndex = layer.load(2);
            let fieldList = getFieldList();
            $.ajax({
                url: "/office_automation/public/index.php/index/admin_c/addWorkTable",
                type:'post',
                data: {
                    tableName     : result.tableName,
                    description   : result.description,
                    fieldList     : fieldList,
                    userList      : userIdList
                },
                success: function(res){
                    layer.close(loadingIndex);
                    if(res.code === 0){
                        console.log(res)
                        var index = layer.alert("发起成功", {
                            title: '提示'
                        }, function () {
                            layer.close(index);
                            location.reload();
                            miniTab.openNewTabByIframe({
                                href:"page/table/index-admin.html",
                                title:"工作表搜索",
                            });
                        });
                    }else{
                        var index = layer.alert("生成失败！", {
                            title: '提示'
                        });
                    }
                },
                error: function(res){

                }
            });
        });
        return false;
    });
});