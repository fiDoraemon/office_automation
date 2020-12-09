layui.use(['form', 'layedit', 'laydate' ,'upload','miniTab'], function () {
    var   form    = layui.form
        , layer   = layui.layer
        , miniTab = layui.miniTab
        , $       = layui.$;

    $('.sortablelist').clickSort();

    var userIdList  = [];   //已经选过的员工
    var fieldCount  = 0;    //当前已经有多少个字段
    var newUserList = [];   //选择的新员工
    var delUserList = [];   //删除员工

    var tableId = getQueryVariable('tableId');
    var creatorId = "";

    //获取工作表信息
    $.ajax({
        url:"/office_automation/public/index.php/index/admin_c/getTableOfId",
        type: "get",
        data:{
            tableId : tableId
        },
        success: function(res){
            if(res.code === 0){
                let data = res.data;
                creatorId = data.creator_id;
                let tableName   = data.table_name;
                let description = data.description;
                let creatorName = data.creator_name;
                let createTime  = data.create_time;
                let fieldList   = data.fieldList;
                let userList    = data.users;
                let status      = data.status;
                fieldCount = fieldList.length;
                $("#table-name").val(tableName);
                $("#description").val(description);
                $("#create-user").val(creatorName);
                $("#create-time").val(createTime);
                initField(fieldList);
                initUser(userList);
                if(status === 1){
                    $("#table-status").append("<input  type=\"checkbox\" lay-filter=\"switch\" name=\"isOpen\" checked lay-skin=\"switch\" lay-text=\"启用|禁用\" class=\"table-status\">");
                }else{
                    $("#table-status").append("<input  type=\"checkbox\" lay-filter=\"switch\" name=\"isOpen\" lay-skin=\"switch\" lay-text=\"启用|禁用\" class=\"table-status\">")
                }
                form.render('checkbox');
            }
        },
        error: function(res){}
    });

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
        let index = $.inArray(userId, userIdList);
        if(index >= 0){
            layer.msg("该员工已在可见名单中！");
            return;
        }
        if ($.inArray(userId, newUserList) === -1) {
            newUserList.push(userId);
            $("#newUserList").append(userInfo);
        }
        var $element = $("#select-user .layui-form-selected dl");
        return false;
    });

    var fieldValue = "<input type=\"text\" style=\"width:500px\" name=\"fieldValue\" lay-verify=\"required\"  placeholder=\"以中文逗号'，'隔开\" class=\"layui-input fieldValue\">";
    //选择字段类型监听
    form.on('select(select-field-type)',function(data){
        let fieldType = data.value;
        var thisDom = $(data.elem); //DOM对象转jq对象
        if(fieldType === "select" || fieldType === "checkbox" ){
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

    //删除可见人名单里面的员工
    $("#userList").on("click", "i", function () {
        let userId = $(this).prev('span').attr("lay-value");
        if(userId === creatorId ){
            return;
        }
        delUserList.push(userId);
        $(this).parent().remove();
    });

    //删除新增人员
    $("#newUserList").on("click", "i", function () {
        var userId = $(this).prev('span').attr("lay-value");
        removeFromArray(newUserList, userId);
        $(this).parent().remove();
    });

    function initField(fieldList){
        for(var i = 0; i < fieldList.length; i++) {
            let id     = fieldList[i].field_id;
            let type   = fieldList[i].type;
            let name   = fieldList[i].name;
            let value  = fieldList[i].value;
            let status = fieldList[i].status;
            let show   = fieldList[i].show;
            let fieldInfo = "<li class=\"sortableitem\" lay-value=\"" + id + "\">\n" +
                "                <div class=\"layui-input-inline\">\n";
            if(show === 1){
                fieldInfo += "<span class=\"isShow\">是否显示：</span><input  type=\"checkbox\" checked lay-filter=\"switch\" name=\"isShow\" lay-skin=\"switch\" lay-text=\"ON|OFF\" class=\"show\">\n" +
                             "</div>\n" +
                             "<div class=\"layui-input-inline\">\n";
            }else{
                fieldInfo += "<span class=\"isShow\">是否显示：</span><input  type=\"checkbox\" lay-filter=\"switch\" name=\"isShow\" lay-skin=\"switch\" lay-text=\"ON|OFF\" class=\"show\">\n" +
                             "</div>" +
                             "<div class=\"layui-input-inline\">\n";
            }
            fieldInfo += " <select name=\"fieldType\" style=\"float: left;\" class=\"fieldType\" lay-filter=\"select-field-type\" lay-verify=\"required\">";
            switch(type){
                case "text":
                    fieldInfo += " <option value=\"text\" selected>单行文本</option>";
                    break;
                case "textarea":
                    fieldInfo += " <option value=\"textarea\" selected>多行文本</option>";
                    break;
                case "select":
                    fieldInfo += " <option value=\"select\" selected>自定义单选</option>";
                    break;
                case "checkbox":
                    fieldInfo += " <option value=\"checkbox\" selected>自定义多选</option>";
                    break;
                case "user":
                    fieldInfo += " <option value=\"user\" selected>单选员工</option>";
                    break;
                case "users":
                    fieldInfo += " <option value=\"users\" selected>多选员工</option>";
                    break;
                case "date":
                    fieldInfo += "  <option value=\"date\" selected>日期</option>";
                    break;
                case "mission":
                    fieldInfo += "  <option value=\"mission\" selected>任务</option>";
                    break;
            }
            fieldInfo += "</select></div>";
            fieldInfo += " <div class=\"layui-input-inline\">\n" +
                "               <input type=\"text\" style=\"float: left;\" name=\"fieldName\" lay-verify=\"required\"  value= \"" + name + "\" class=\"layui-input fieldName\">\n" +
                "           </div>";
            fieldInfo += "<div class=\"layui-input-inline field-btn\">\n" +
                "             <a class=\"layui-btn-xs data-count-edit icon-btn down movedown\" lay-event=\"down\"><i class=\"fa fa-arrow-down\" aria-hidden=\"true\"></i></a>\n" +
                "             <a class=\"layui-btn-xs data-count-edit icon-btn up moveup\" lay-event=\"up\"><i class=\"fa fa-arrow-up\" aria-hidden=\"true\"></i></a>\n" +
                "             <a class=\"layui-btn layui-btn-xs data-count-edit icon-btn add\" lay-event=\"add\"><i class=\"layui-icon layui-icon-addition\"></i></a>\n"
            if(status  === 1){
                fieldInfo += "<input  type=\"checkbox\" lay-filter=\"switch\" name=\"isOpen\" checked lay-skin=\"switch\" lay-text=\"启用|禁用\" class=\"status\">\n" +
                    "          </div>";
            }else{
                fieldInfo += "<input  type=\"checkbox\" lay-filter=\"switch\" name=\"isOpen\" lay-skin=\"switch\" lay-text=\"启用|禁用\" class=\"status\">\n" +
                    "          </div>";
            }
            if(type === "select" || type === "checkbox"){
                fieldInfo += " <div class=\"layui-input-inline field-value\">" +
                    "      <input type=\"text\" style=\"width:500px\" name=\"fieldValue\" value=\"" + value + "\" class=\"layui-input fieldValue\">" +
                    "</div>";
            }
            $("#field-List").append(fieldInfo);
        }
        form.render('checkbox');
        form.render('select');
    }

    function initUser(userList){
        for(var i = 0; i < userList.length; i++) {
            userIdList.push( userList[i].user_id);
            let userInfo = ' <a href="javascript:;" class="test">' +
                ' <span lay-value="' + userList[i].user_id + '">' + userList[i].user_name + '(' + userList[i].user_id + ')' + '</span>' +
                ' <i class="layui-icon layui-icon-close"></i>' +
                '</a>';
            $("#userList").append(userInfo);
        }
    }
    var newField = " <li class=\"new-field sortableitem\" lay-value=\"\">\n" +
        "                                <div class=\"layui-input-inline\">\n" +
        "                                   <span class='isShow'>是否显示：</span><input  type=\"checkbox\" lay-filter=\"switch\" name=\"isShow\" lay-skin=\"switch\" lay-text=\"ON|OFF\" class=\"show\">" +
        "                                </div>" +
        "                                <div class=\"layui-input-inline\">\n" +
        "                                    <select name=\"fieldType\" style=\"float: left;\" class=\"fieldType\" lay-filter=\"select-field-type\" lay-verify=\"required\">\n" +
        "                                        <option value=\"text\" selected>单行文本</option>\n" +
        "                                        <option value=\"textarea\">多行文本</option>\n" +
        "                                        <option value=\"select\">自定义单选</option>" +
        "                                        <option value=\"checkbox\">自定义多选</option>\n" +
        "                                        <option value=\"user\">单选员工</option>\n" +
        "                                        <option value=\"users\">多选员工</option>\n" +
        "                                        <option value=\"date\">日期</option>\n" +
        "                                        <option value=\"mission\">任务</option>\n" +
        "                                    </select>\n" +
        "                                </div>\n" +
        "                                <div class=\"layui-input-inline\">\n" +
        "                                    <input type=\"text\" style=\"float: left;\" name=\"fieldName\" lay-verify=\"required\"  placeholder=\"请输入字段名\" class=\"layui-input fieldName\">\n" +
        "                                </div>\n" +
        "                                <div class=\"layui-input-inline field-btn\">\n" +
        "                                    <a class=\"layui-btn-xs data-count-edit icon-btn down movedown\" lay-event=\"down\"><i class=\"fa fa-arrow-down\" aria-hidden=\"true\"></i></a>\n" +
        "                                    <a class=\"layui-btn-xs data-count-edit icon-btn up moveup\" lay-event=\"up\"><i class=\"fa fa-arrow-up\" aria-hidden=\"true\"></i></a>\n" +
        "                                    <a class=\"layui-btn layui-btn-xs data-count-edit icon-btn add\" lay-event=\"add\"><i class=\"layui-icon layui-icon-addition\"></i></a>\n" +
        "                                    <a class=\"layui-btn layui-btn-xs layui-btn-danger icon-btn delete\" lay-event=\"delete\"><i class=\"layui-icon layui-icon-subtraction\"></i></a>\n" +
        "                                </div>\n" +
        "                                <div class=\"layui-input-inline field-value\">\n" +
        "                                </div>\n" +
        "                            </li>";

    function getFieldList(){
        let fieldInfos = [];
        let sort = 1;
        $('ul li').each(function(){
            let id = $(this).attr("lay-value");
            let fieldType    = $(this).find(".fieldType").val();
            let fieldName    = $(this).find(".fieldName").val();
            let fieldValue   = $(this).find(".fieldValue").val();
            let status       = $(this).find(".status").next().hasClass("layui-form-onswitch") ? 1 : 0;
            let isShow       = $(this).find(".show").next().hasClass("layui-form-onswitch") ? 1 : 0;
            let field = "";
            if(fieldType === "select" || fieldType === "checkbox"){
                field = {"sort": sort++, "id": id, "fieldType": fieldType,"fieldName": fieldName,"isShow":isShow,"fieldValue": fieldValue};
            }else{
                field = {"sort": sort++, "id": id, "fieldType": fieldType,"fieldName": fieldName,"isShow":isShow};
            }
            if(id !== ""){
                field.status = status;
            }
            fieldInfos.push(field);
        });
        return fieldInfos;
    }

    //监听提交
    form.on('submit(save)', function (data) {
        var result = data.field;
        layer.confirm('确定修改工作表？', {icon: 3, title:'提示'}, function(index){
            layer.close(index);
            let loadingIndex = layer.load(2);
            let fieldList = getFieldList();
            var status    = $(".table-status").next().hasClass("layui-form-onswitch") ? 1 : 0;
            $.ajax({
                url: "/office_automation/public/index.php/index/admin_c/updateTable",
                type:'post',
                data: {
                    tableId       : tableId,
                    tableName     : result.tableName,
                    status        : status,
                    description   : result.description,
                    fieldList     : fieldList,
                    newUserList   : newUserList,
                    delUserList   : delUserList
                },
                success: function(res){
                    layer.close(loadingIndex);
                    if(res.code === 0){
                        let index = layer.alert("修改成功", {
                            title: '提示'
                        }, function () {
                            layer.close(index);
                            location.reload();
                        });
                    }else{
                        let index = layer.alert("修改失败！", {
                            title: '提示'
                        });
                    }
                },
                error: function(res){}
            });
        });
        return false;
    });

    $("#refresh").on("click",function(){
        location.reload();
    });
});