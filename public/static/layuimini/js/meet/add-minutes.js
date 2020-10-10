var userIdList = []; //已经选择的应到会议员工id数组
var uploadList = [];
var form;
var hostId;  //根绝这个变量让会议发起人必须在应到会议名单中
layui.use(['form'], function () {
    form = layui.form
});

//进入页面先判断是否有临时保存的会议信息
$.ajax({
    url: "/office_automation/public/index.php/index/minute_c/hasTempMinute",
    type:'get',
    timeout: 1000,
    data: {},
    success: function(res){
        var data = res.data;
        if(res.code === 0){
            layer.confirm('是否读取临时保存的会议纪要？', {
                btn: ['读取','取消'] //按钮
            }, function(index){
                //给页面填充值
                $("#select-minute-type").val(data.minute_type);
                $("#minute-theme").val(data.minute_theme);
                $("#select-project-code").val(data.project);
                $("#date").val(data.minute_date);
                $("#time").val(data.minute_time);
                $("#place").val(data.place);
                $("#minute-resolution").val(data.resolution);
                $("#minute-context").val(data.record);
                var attend_users = data.minuteAttends;
                var userId = "";
                var userNameList = "";
                for (var i = 0; i < attend_users.length; i++){
                    if(i>0){
                        userId += ",";
                        userNameList += "，";
                    }
                    userId += attend_users[i]["user_id"];
                    userNameList += attend_users[i]["user"].user_name;
                }
                $('#list-users').attr('ts-selected', userId);
                $('#list-users').val(userNameList);
                form.render("select");
                layer.close(index);
            }, function(){

            });
        }
    },
    error: function(data){
    }
});

/**
 * 获取所有部门信息和会议类型信息
 */
function selectAddInfo(form){
    $.ajax({
        url: "/office_automation/public/index.php/index/minute_c/getAddInfo",
        type:'post',
        timeout: 1000,
        data: {},
        success: function(res){
            var hostName = res["data"]["hostName"];
            var departmentName = res["data"]["departmentName"];
            hostId = res["data"]["hostId"];
            //会议主持人名字
            $("#host-name").val(hostName);
            //所属部门
            $("#department-name").val(departmentName);
            //会议类型信息
            var minuteTypeArray = res["data"]["minuteType"];
            for (var i = 0; i < minuteTypeArray.length; i++){
                var $option = "<option value='" + minuteTypeArray[i]["type_id"] + "'>" + minuteTypeArray[i]["type_name"] + "</option>";
                $("#select-minute-type").append($option);
            }
            //项目代号
            var projectArray = res["data"]["projectType"];
            for (var i = 0; i < projectArray.length; i++){
                var $option = "<option value='" + projectArray[i]["project_code"] + "'>" + projectArray[i]["project_code"] + "</option>";
                $("#select-project-code").append($option);
            }
            if( $('#list-users').val() === null ||  $('#list-users').val() === ""){
                $('#list-users').attr('ts-selected', hostId);
                $('#list-users').val(hostName);
            }
            //需要重新加载
            form.render('select');
        },
        error: function(data){
        }
    });
}

//临时保存
$("#temporarySave").on("click",function () {
    saveTemp();
});
//快捷键临时保存
$(window).keydown(function (e) {
    if (e.keyCode === 83 && e.ctrlKey) {
        e.preventDefault();
        saveTemp();
    }
});

function saveTemp(){
    //判断应到列表中是否存在会议发起人，若没有则添加
    if($.inArray(hostId , userIdList) === -1){
        userIdList.push(hostId);
    }
    var minute_type  = $("#select-minute-type").val();
    var minute_theme = $("#minute-theme").val();
    var project_code = $("#select-project-code").val();
    var date  = $("#date").val();
    var time  = $("#time").val();
    var place = $("#place").val();
    var attend_users = userIdList;
    var minute_resolution = $("#minute-resolution").val();
    var minute_context = $("#minute-context").val();
    $.ajax({ //临时保存
        url: "/office_automation/public/index.php/index/minute_c/saveTemp",
        type: 'post',
        timeout: 1000,
        data: {
            minute_type     : minute_type,
            minute_theme    : minute_theme,
            project_code    : project_code,
            date            : date,
            time            : time,
            place           : place,
            attend_users    : attend_users,
            minute_resolution : minute_resolution,
            minute_context  : minute_context
        },
        success: function (res) {
            if(res.code === 0){
                layer.msg('临时保存成功');
            }else{
                layer.msg('已保存至最新！');
            }
        },
        error: function (res) {
            layer.msg('临时保存失败');
        }
    });
}






