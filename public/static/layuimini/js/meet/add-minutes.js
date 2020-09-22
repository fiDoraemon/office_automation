/**
 * 获取所有部门信息和会议类型信息
 */
function selectAddInfo(form){
    $.ajax({
        url: "/office_automation/public/index.php/index/meeting_c/getAddInfo",
        type:'post',
        timeout: 1000,
        data: {
        },
        success: function(res){
            console.log(res);
            //会议主持人名字
            $("#host-name").val(res["data"]["hostName"]);
            //所属部门
            $("#department-name").val(res["data"]["departmentName"]);
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
            //需要重新加载
            form.render('select');
        },
        error: function(data){
        }
    });
}




