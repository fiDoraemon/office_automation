function getUrlParam(name)
{
    var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)"); //构造一个含有目标参数的正则表达式对象
    var r = window.location.search.substr(1).match(reg);  //匹配目标参数
    if (r!=null) return unescape(r[2]); return null; //返回参数值
}
var minute_id = getUrlParam("minute_id");
$.ajax({
    url: "/office_automation/public/index.php/index/meeting_c/getMinute",
    type:'post',
    timeout: 1000,
    data: {
        minuteId : minute_id,
    },
    success: function(res){
        console.log(res)
        var data = res.data;
        var attendArray = data.minuteAttends;
        var attendusers = "";
        for (var i = 0; i < attendArray.length; i++) {
            attendusers += attendArray[i].user.user_name + ";";
        }
        $("#department-name").val(data.department.department_name);
        $("#minute-theme").val(data.minute_theme);
        $("#complete-status").val("完成个锤子");
        $("#project-code").val(data.project);
        $("#project-stage").val(data.projectStage.stage_name);
        $("#date").val(data.minute_date);
        $("#time").val(data.minute_time);
        $("#minute-place").val(data.place);
        $("#minute-host").val(data.user.user_name);
        $("#attend-user").val(attendusers);
        $("#minute-resolution").val(data.resolution);
        $("#minute-context").val(data.record);
    },
    error: function(res){
        console.log(res)
    }
});


