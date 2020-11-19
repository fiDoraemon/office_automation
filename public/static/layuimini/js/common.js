layui.use(['form'], function () {
    var $ = layui.$,
        form = layui.form;

    // 选择部门
    form.on('select(multipleSelect-department)',function(data){
        var userSelect = $(data.elem).attr('select-user');          // 对应的用户下拉列表id
        if(data.value == '') {
            return false;
        }
        $.get(
            "/office_automation/public/index.php/index/user_c/getUserOfDepartment?departmentId=" + data.value,
            function(res){
                $('#' + userSelect).empty();
                var userList = res.data;
                var element = `<option value="">请选择用户</option>`;
                for (i in userList){
                    element += `<option value="${userList[i].user_id}">${userList[i].user_name}</option>`;
                }
                $('#' + userSelect).append(element);
                form.render('select');
            }
        );

        return false;
    });
    // 选择用户
    form.on('select(multipleSelect-user)',function(data){
        var userSelect = $(data.elem).attr('id');
        if(data.value == '') {
            return false;
        }
        var userName = data.elem[data.elem.selectedIndex].text;
        var userId = data.value;
        var element = `
        <a href="javascript:;">
            <span lay-value="${userId}">${userName}</span>
            <i class="layui-icon layui-icon-close"></i>
        </a>
        `;
        // 判断用户列表中是否存在
        if($.inArray(userId , userIdsList[userSelect]) == -1){
            userIdsList[userSelect].push(userId);
            $('#' + userSelect + '-userList').append(element);
        }

        return false;
    });
    // 删除已选择的用户
    $("body").on("click", ".multipleSelect i", function () {
        var userSelect = $(this).parent().parent().attr('id').split('-')[0];
        var userId = $(this).prev('span').attr("lay-value");
        removeFromArray(userIdsList[userSelect], userId);
        $(this).parent().remove();
    });
});
// 休眠 单位：毫秒
function sleep(numberMillis) {
    var now = new Date();
    var exitTime = now.getTime() + numberMillis;
    while (true) {
        now = new Date();
        if (now.getTime() > exitTime) {
            return;
        }
    }
}

// 获取 url 参数值
function getQueryVariable(variable) {
    var query = window.location.search.substring(1);
    var vars = query.split("&");

    for (var i = 0; i < vars.length; i++) {
        var pair = vars[i].split("=");
        if (pair[0] == variable) {
            return pair[1];
        }
    }

    return false;
}

// 阻止冒泡
function stopPropagation(e) {
    var e = e || window.event;
    if(e.stopPropagation) { //W3C阻止冒泡方法
        e.stopPropagation();
    } else {
        e.cancelBubble = true; //IE阻止冒泡方法
    }
}

//设置cookie
function setCookie(cname,cvalue,exdays)
{
    var d = new Date();
    d.setTime(d.getTime()+(exdays*24*60*60*1000));
    var expires = "expires=" + d.toGMTString() + "; path=/office_automation/public/static/layuimini"
    document.cookie = cname + "=" + cvalue + "; " + expires;
}

//获取cookie
function getCookie(cname)
{
    var name = cname + "=";
    var ca = document.cookie.split(';');
    for(var i=0; i<ca.length; i++)
    {
        var c = ca[i].trim();
        if (c.indexOf(name)==0) return c.substring(name.length,c.length);
    }
    return "";
}

// 在数组中删除某一个元素
function removeFromArray (arr, val) {
    var index = arr.indexOf(val);
    if (index >= 0) {
        arr.splice(index, 1);
    }

    return arr;
}

// 填充部门下拉列表
function departmentSelect() {
    layui.use(['form'], function () {
        var $ = layui.$,
            form = layui.form;

        $.get(
            "/office_automation/public/index.php/index/user_c/getAllDepartment",
            function (res) {
                var departmentList = res.data;
                var element = '';
                for (i in departmentList) {
                    element += `<option value="${departmentList[i].department_id}">${departmentList[i].department_name}</option>`;
                }
                $(".select-department").append(element);
                form.render('select');
            }
        );
    });
}

// 处理人下拉表格
function userSelectTable(tableSelect, element) {
    layui.use(['tableSelect'], function () {
        var tableSelect = layui.tableSelect;

        tableSelect.render({
            elem: element,
            checkedKey: 'user_id',
            searchPlaceholder: '用户/部门关键词',
            table: {
                url: '/office_automation/public/index.php/index/user_c/getAllUsers'
                ,cols: [[
                    { type: 'radio' },
                    { field: 'user_id', title: '工号' },
                    { field: 'user_name', title: '姓名' },
                    { field: 'department_name', title: '部门'}
                ]]
            },
            done: function (elem, data) {
                var result = data.data;
                if(result.length != 0) {
                    elem.val(result[0].user_name);
                } else {
                    elem.val('');
                }
            }
        });
    });
}

// 打开任务弹窗
function toMissionPage(missionId){
    layui.use([], function () {
        layer.open({
            title: '任务详情',
            type: 2,
            content: 'read.html?id=' + missionId,
            shade: 0.2,
            maxmin:true,
            shadeClose: true,
            area: ['100%', '100%'],
        });
    });
}