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
var user_id = getUrlParam("user_id");

layui.use(['form','miniTab'], function () {
    var form = layui.form,
        layer = layui.layer,
        miniTab = layui.miniTab;

    var departmentInfo = "";

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
            console.log("部门信息");
            console.log(res.data);
            var departmentArray = res.data;;
            for (var i = 0; i < departmentArray.length; i++){
                departmentInfo += "<option value='" + departmentArray[i]["department_id"] + "'>" + departmentArray[i]["department_name"] + "</option>";
            }
            $("#departmentSelect").append(departmentInfo);
            selectUserInfo();  //防止查询部门信息比查询用户信息早,不然会出现用户的部门显示不成功的问题
            //需要重新加载
            form.render('select');
        },
        error: function(res){
        }
    });

    /**
     * 进入页面马上获取用户的基本信息
     */
    function selectUserInfo(){
        $.ajax({
            url: "/office_automation/public/index.php/index/admin_c/getUserInfo",
            type:'get',
            timeout: 2000,
            data: {
                userId : user_id
            },
            success: function(data){
                var code = data.code;
                //获取成功，显示用户信息
                if(code === 0){
                    var user_info = data['data'];
                    var user_id = user_info['user_id'];
                    var user_name = user_info['user_name'];
                    var user_department = user_info['department_id'];
                    var user_phone = user_info['phone'];
                    var user_email = user_info['email'];
                    $("#user_id").val(user_id);
                    $("#user_name").val(user_name);
                    $("#departmentSelect").val(user_department);
                    $("#user_phone").val(user_phone);
                    $("#user_email").val(user_email);
                    //需要重新加载
                    form.render('select');
                }
            },
            error: function(res){
                var index = layer.alert("请求出错！", {
                    title: '系统提示'
                });
            }
        });
    }

    //监听提交
    form.on('submit(saveBtn)', function (data) {
        var result       = data.field;
        var userId       = result.user_id;
        var userName     = result.user_name.trim().replace(/\s/g,"");
        var departmentId = result.departmentSelect;
        var phone        = result.phone.trim().replace(/\s/g,"");
        var email        = result.email.trim().replace(/\s/g,"");
        $.ajax({
            url: "/office_automation/public/index.php/index/admin_c/updateUser",
            type:'post',
            data:{
                userId      : userId,
                userName    : userName,
                departmentId: departmentId,
                phone       : phone,
                email       : email
            },
            success: function(data){
                var code = data['code'];
                if(code === 0){
                    var index = layer.alert("用户信息修改成功", {
                        title: '系统提示'
                    }, function () {
                        var frameIndex = parent.layer.getFrameIndex(window.name);
                        parent.layer.close(frameIndex);
                        layer.close(index);
                    });
                }else{
                    var index = layer.alert("用户信息修改失败！", {
                        title: '系统提示'
                    });
                }
            },
            error: function(data){
                var index = layer.alert("请求出错！", {
                    title: '系统提示'
                });
            }
        });
        return false;
    });
});