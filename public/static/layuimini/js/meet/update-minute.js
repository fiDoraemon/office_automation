function getUrlParam(name)
{
    var reg = new RegExp("(^|&)"+ name +"=([^&]*)(&|$)"); //构造一个含有目标参数的正则表达式对象
    var r = window.location.search.substr(1).match(reg);  //匹配目标参数
    if (r!=null) return unescape(r[2]); return null; //返回参数值
}
//全局标识此次会议id
var minute_id = getUrlParam("minute_id");

$.ajax({
    url: "/office_automation/public/index.php/index/minute_c/getMinuteInfo",
    type:'post',
    timeout: 1000,
    data: {
        minuteId : minute_id,
    },
    success: function(res){
        console.log(res)
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
        for (var i = 0; i < attendArray.length; i++) {
            attendusers += attendArray[i].user.user_name + ";";
        }
        for (var i = 0; i < attendedArray.length; i++) {
            attendedusers += attendedArray[i].user.user_name + ";";
        }
        layui.use(['table'], function (){
            var table = layui.table;
            var missiondata = table.cache["minute-table"];
            console.log(missiondata);
            $count = missionArray.length;
            for (var i = 0; i < missionArray.length; i++) {
                switch(missionArray[i].mission.status){
                    case "未开始":  $notStarted++; break;
                    case "处理中":  $processing++; break;
                    case "已完成":  $finish++; break;
                    case "已暂停":  $suspend++; break;
                }
                missiondata.push( missionArray[i].mission);
            }
            //下面表格需要重载一下 才会刷新显示.
            table.reload("minute-table", {
                data: missiondata,
            });
            console.log();
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
    },
    error: function(res){
        console.log(res)
    }
});


