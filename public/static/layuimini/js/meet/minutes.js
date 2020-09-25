/**
 * 获取所有部门信息和会议类型信息
 */
function selectInfo(form){
    $.ajax({
        url: "/office_automation/public/index.php/index/minute_c/getInfo",
        type:'post',
        timeout: 1000,
        data: {
        },
        success: function(res){
            console.log(res);
            var projectArray = res["data"]["projectType"];
            var minuteArray = res["data"]["minuteType"];
            for (var i = 0; i < projectArray.length; i++){
                var $option = "<option value='" + projectArray[i]["project_code"] + "'>" + projectArray[i]["project_code"] + "</option>";
                $("#select-project-code").append($option);
            }
            for (var i = 0; i < minuteArray.length; i++){
                var $option = "<option value='" + minuteArray[i]["type_id"] + "'>" + minuteArray[i]["type_name"] + "</option>";
                $("#select-for-meet").append($option);
            }
            //需要重新加载
            form.render('select');
        },
        error: function(data){
        }
    });
}




