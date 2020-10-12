//添加实际到会人员
var attended = [];
//添加应到会人员
var newAttended = [];
//新增基本任务清单
var newMission = [];
//已上传文件
var uploadList = [];

var departmentInfo = "";

/**
 * 获取url传输的会议id
 */
function getUrlParam(name)
{
    var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)"); //构造一个含有目标参数的正则表达式对象
    var r = window.location.search.substr(1).match(reg);  //匹配目标参数
    if (r!=null) return unescape(r[2]); return null;    //返回参数值
}

//全局标识此次会议id
var minute_id = getUrlParam("minute_id");

$("#minute-id").html(minute_id);

//进入页面后检查是否有修改会议的权限，以及是否有临时保存的会议信息
$.ajax({
    url: "/office_automation/public/index.php/index/minute_c/hasTempMinute",
    type:'get',
    timeout: 1000,
    data: {
        minuteId : minute_id,
    },
    success: function(res){
        console.log(res);
        switch (res.code) {
            case 0  : //有临时保存的会议信息
                layer.confirm('是否读取临时保存的会议纪要？', {
                    btn: ['读取','取消'] //按钮
                }, function(index){
                    getTempMinuteInfo();
                    layer.close(index);
                }, function(){
                    tempMinuteInfo();
                });
                break;
            case 27 : //没有修改权限
                tempMinuteInfo();
                $('html').css("pointer-events", "none");
                break;
            case 28 : //没有临时保存的信息
                tempMinuteInfo();
                break;
        }
    },
    error: function(res){

    }
});

/**
 * 获取部门信息
 */
$.ajax({
    url: "/office_automation/public/index.php/index/minute_c/getAllDepartment",
    type:'get',
    timeout: 2000,
    data: {
    },
    success: function(res){
        var departmentArray = res.data;;
        for (var i = 0; i < departmentArray.length; i++){
            departmentInfo += "<option value='" + departmentArray[i]["department_id"] + "'>" + departmentArray[i]["department_name"] + "</option>";
        }
    },
    error: function(res){
    }
});

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

/**
 * 获取新增任务清单数据
 * @returns {Array}
 */
function getMissionInfo(){
    var missionInfos = [];
    $('.missionItem').each(function(){
        var missionTitle= $(this).find(".title").val();
        var assigneeId  = $(this).find(".responsible").val();
        var finishDate  = $(this).find(".finish-date").val();
        var description = $(this).find(".describe").val();
        var mis = {"missionTitle":missionTitle,"assigneeId":assigneeId,"finishDate":finishDate,"description":description};
        missionInfos.push(mis);
    });
    return missionInfos;
}

/**
 * 获取临时保存的会议信息
 */
function getTempMinuteInfo(){
    $.ajax({ //临时保存
        url: "/office_automation/public/index.php/index/minute_c/getTempMinuteInfo",
        type: 'get',
        timeout: 1000,
        data: {
            minuteId : minute_id
        },
        success: function (res) {
            console.log(res);
            var data = res.data;
            var attendArray = data.minuteAttends;
            var attendedArray = data.minuteAttendeds;
            var missionArray = data.minuteMission;                //实际的任务
            var minuteTempMission = data.minuteTempMission;       //临时保存的会议任务
            var newMissionArray = minuteTempMission[0].new_temp_list;  //任务清单
            var attendusers = "";
            var attendedusers = "";
            var $count = 0;
            var $finish = 0;     //完成
            var $notStarted = 0; //未开始
            var $suspend = 0;    //暂停
            var $processing = 0; //处理中
            var element = '';    //上传附件
            for (var i = 0; i < attendArray.length; i++) {
                attendusers += attendArray[i].user.user_name + ";";
            }
            for (var i = 0; i < attendedArray.length; i++) {
                attendedusers += attendedArray[i].user.user_name + ";";
            }
            layui.use(['table'], function (){
                var table = layui.table;
                var missiondata = table.cache["minute-table"];
                $count = missionArray.length;
                for (var i = 0; i < missionArray.length; i++) {
                    switch(missionArray[i].status){
                        case "未开始":  $notStarted++; break;
                        case "处理中":  $processing++; break;
                        case "已完成":  $finish++; break;
                        case "已暂停":  $suspend++; break;
                    }
                    missiondata.push( missionArray[i]);
                }
                //下面表格需要重载一下 才会刷新显示.
                table.reload("minute-table", {
                    data: missiondata,
                });
            });
            var finishStatus = "总数: " + $count + "|完成:" + $finish + "|未开始:" + $notStarted + "|暂停:" + $suspend + "|处理中:" + $processing;
            $("#department-name").val(data.department.department_name);
            $("#minute-theme").val(data.minute_theme);
            $("#complete-status").val(finishStatus);
            $("#project-code").val(data.project);
            $("#project-stage").val(data.projectStage.stage_name);
            $("#date").val(data.minute_date);
            $("#time").val(data.minute_time);
            $("#minute-place").val(data.place);
            $("#minute-host").val(data.user.user_name);
            $("#attend-user").val(attendusers);
            $("#attended-user").val(attendedusers);
            $("#minute-resolution").val(data.resolution);
            $("#minute-context").val(data.record);
            $("#add-mission").val(newMissionArray);
            $('#add-mission').attr('ts-selected', newMissionArray);
            var form;
            layui.use(['form'], function () {                                    //-------------------------------------------------------
                form = layui.form;
                form.render("select");
            });
            console.log("mission:")
            console.log(missionArray);
            for(var i = 0; i < minuteTempMission.length; i++){
                var missionTitle = minuteTempMission[i].mission_title;
                var finishDate = minuteTempMission[i].finish_date;
                var description = minuteTempMission[i].description;
                var assigneeId = minuteTempMission[i].assignee_id;
                var assigneeName = minuteTempMission[i].assignee_name;
                if(assigneeName == null || assigneeName == ""){
                    assigneeName = "--请先选择部门--";
                }
                var $missionInfo = "<tr class='missionItem'>\n" +
                    "                        <td><input type=\"text\" name=\"title\" lay-verify=\"required\" placeholder=\"请输入标题\" autocomplete=\"off\" class=\"layui-input title\" value="+ missionTitle +"></td>\n" +
                    "                        <td>\n" +
                    "                            <select class=\"departmentSelect\" name=\"departmentSelect\" lay-filter=\"departmentSelect\">\n" +
                    "                                <option value=\"\">--选择部门--</option>\n" +
                    departmentInfo +
                    "                            </select>\n" +
                    "                        </td>\n" +
                    "                        <td>\n" +
                    "                            <select class=\"userSelect responsible\" name=\"userSelect\" lay-verify=\"required\" lay-filter=\"userSelect\">\n" +
                    "                                <option value=" + assigneeId + "> "+ assigneeName +"</option>\n" +
                    "                            </select>\n" +
                    "                        </td>\n" +
                    "                        <td><input type=\"date\" name=\"dateTime\" autocomplete=\"off\" lay-verify=\"required\" class=\"layui-input finish-date\" value="+ finishDate +"></td>\n" +
                    "                        <td><input type=\"text\" name=\"title\" placeholder=\"请输入任务描述\" autocomplete=\"off\" lay-verify=\"required\" class=\"layui-input describe\" value="+ description +"></td>\n" +
                    "                        <td> <a class=\"layui-btn layui-btn-xs layui-btn-danger data-count-delete deleteMission\">删除</a></td>\n" +
                    "                    </tr>";
                $("#addMissionTable").append($missionInfo);
                form.render();
            }
            //上传附件
            var attachmentList = data.attachments;
            element = '';
            for(i in attachmentList) {
                element += `
                        <tr>
                            <td>${attachmentList[i].source_name}</td>
                            <td>${attachmentList[i].file_size}</td>
                            <td>已上传</td>
                            <td>
                                <a class="layui-btn layui-btn-xs" href="${attachmentList[i].save_path}" download="${attachmentList[i].source_name}">下载</a>
                                <button class="layui-btn layui-btn-xs layui-btn-danger delete" attachment_id="${attachmentList[i].attachment_id}">删除</button>
                            </td>
                        </tr>
                        `;
            }
            $('#fileList').append(element);
            // 删除附件
            $('#fileList').find('.delete').click(function () {
                var attachment_id = $(this).attr('attachment_id');
                var tr = $(this).parent().parent();
                layer.confirm('确定删除？', {icon: 3, title:'提示'}, function(index) {
                    layer.close(index);
                    $.ajax({
                        url: "/office_automation/public/attachment/" + attachment_id,
                        type: 'delete',
                        success: function (res) {
                            if (res.code == 0) {
                                layer.msg('删除成功！');
                                tr.remove();
                            } else {
                                layer.msg('删除失败！');
                            }
                        }
                    });
                });
            });
        },
        error: function (res) {
            layer.msg('获取失败');
        }
    });
}

/**
 * 临时保存
 */
function saveTemp(){
    var minuteResolution  = $("#minute-resolution").val();
    var minuteContext     = $("#minute-context").val();
    var minuteMission     = getMissionInfo();
    console.log("newMission:");
    console.log(newMission);
    $.ajax({ //临时保存
        url: "/office_automation/public/index.php/index/minute_c/saveTemp",
        type: 'post',
        timeout: 1000,
        data: {
            minuteId        : minute_id,
            attendList      : attended,         //已到会人员（数组）
            newAttended     : newAttended,      //新增应到会人
            newMission      : newMission,       //关联新任务（数组），在原有的任务中选择
            minuteResolution: minuteResolution, //会议决议
            minuteContext   : minuteContext,    //会议记录
            minuteMission   : minuteMission,    //新会议任务（数组）
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

/**
 * 查看是否有修改权限，和是否有临时保存的会议信息
 */
function tempMinuteInfo(){
    $.ajax({
        url: "/office_automation/public/index.php/index/minute_c/getMinuteInfo",
        type:'post',
        timeout: 1000,
        data: {
            minuteId : minute_id,
        },
        success: function(res){
            var data = res.data;
            var attendArray = data.minuteAttends;
            var attendedArray = data.minuteAttendeds;
            var missionArray = data.minuteMission;
            var attendusers = "";
            var attendedusers = "";
            var $count = 0;
            var $finish = 0;     //完成
            var $notStarted = 0; //未开始
            var $suspend = 0;    //暂停
            var $processing = 0; //处理中
            var element = '';    //上传附件
            for (var i = 0; i < attendArray.length; i++) {
                attendusers += attendArray[i].user.user_name + ";";
            }
            for (var i = 0; i < attendedArray.length; i++) {
                attendedusers += attendedArray[i].user.user_name + ";";
            }
            layui.use(['table'], function (){
                var table = layui.table;
                var missiondata = table.cache["minute-table"];
                $count = missionArray.length;
                for (var i = 0; i < missionArray.length; i++) {
                    switch(missionArray[i].status){
                        case "未开始":  $notStarted++; break;
                        case "处理中":  $processing++; break;
                        case "已完成":  $finish++; break;
                        case "已暂停":  $suspend++; break;
                    }
                    missiondata.push( missionArray[i]);
                }
                //下面表格需要重载一下 才会刷新显示.
                table.reload("minute-table", {
                    data: missiondata,
                });
            });
            var finishStatus = "总数: " + $count + "|完成:" + $finish + "|未开始:" + $notStarted + "|暂停:" + $suspend + "|处理中:" + $processing;
            $("#department-name").val(data.department.department_name);
            $("#minute-theme").val(data.minute_theme);
            $("#complete-status").val(finishStatus);
            $("#project-code").val(data.project);
            $("#project-stage").val(data.projectStage.stage_name);
            $("#date").val(data.minute_date);
            $("#time").val(data.minute_time);
            $("#minute-place").val(data.place);
            $("#minute-host").val(data.user.user_name);
            $("#attend-user").val(attendusers);
            $("#attended-user").val(attendedusers);
            $("#minute-resolution").val(data.resolution);
            $("#minute-context").val(data.record);
            //上传附件
            var attachmentList = data.attachments;
            element = '';
            for(i in attachmentList) {
                element += `
                        <tr>
                            <td>${attachmentList[i].source_name}</td>
                            <td>${attachmentList[i].file_size}</td>
                            <td>已上传</td>
                            <td>
                                <a class="layui-btn layui-btn-xs" href="${attachmentList[i].save_path}" download="${attachmentList[i].source_name}">下载</a>
                                <button class="layui-btn layui-btn-xs layui-btn-danger delete" attachment_id="${attachmentList[i].attachment_id}">删除</button>
                            </td>
                        </tr>
                        `;
            }
            $('#fileList').append(element);
            // 删除附件
            $('#fileList').find('.delete').click(function () {
                var attachment_id = $(this).attr('attachment_id');
                var tr = $(this).parent().parent();
                layer.confirm('确定删除？', {icon: 3, title:'提示'}, function(index) {
                    layer.close(index);
                    $.ajax({
                        url: "/office_automation/public/attachment/" + attachment_id,
                        type: 'delete',
                        success: function (res) {
                            if (res.code == 0) {
                                layer.msg('删除成功！');
                                tr.remove();
                            } else {
                                layer.msg('删除失败！');
                            }
                        }
                    });
                });
            });
        },
        error: function(res){
        }
    });
}

