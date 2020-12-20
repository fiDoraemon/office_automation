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

    // 查看任务详情（从父页面打开）
    $('body').on('click', '.to-mission2', function () {
        parent.toMissionPage($(this).attr('mission-id'));
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

// 设置cookie
function setCookie(cname,cvalue,exdays)
{
    var d = new Date();
    d.setTime(d.getTime()+(exdays*24*60*60*1000));
    var expires = "expires=" + d.toGMTString() + "; path=/office_automation/public/static/layuimini"
    document.cookie = cname + "=" + cvalue + "; " + expires;
}

// 获取cookie
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

/**
 * 计算文件大小(保留两位小数)
 * @param size 字节大小
 * @returns {string}
 */
function getfilesize(size) {
    if (!size)
        return "";
    var num = 1024.00; //byte
    if (size < num)
        return size + "B";
    if (size < Math.pow(num, 2))
        return (size / num).toFixed(2) + "K"; //kb
    if (size < Math.pow(num, 3))
        return (size / Math.pow(num, 2)).toFixed(2) + "M"; //M
    if (size < Math.pow(num, 4))
        return (size / Math.pow(num, 3)).toFixed(2) + "G"; //G
    return (size / Math.pow(num, 4)).toFixed(2) + "T"; //T
}

/**
 * 将dom元素转为字符串
 * @param node
 * @returns {string}
 */
function nodeToString(node) {
    var tmpNode = document.createElement("div");
    tmpNode.appendChild(node.cloneNode(true));
    var str = tmpNode.innerHTML;
    tmpNode = node = null;

    return str;
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
function userSelectTable(element) {
    layui.use(['tableSelect'], function () {
        var tableSelect = layui.tableSelect;

        tableSelect.render({
            elem: element,
            checkedKey: 'user_id',
            searchPlaceholder: '用户/部门关键词',
            height:'250',
            width:'400',
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

// 任务下拉表格
function missionSelectTable(element) {
    layui.use(['tableSelect'], function () {
        var tableSelect = layui.tableSelect;

        tableSelect.render({
            elem: element,
            checkedKey: 'mission_id',
            searchPlaceholder: '任务号/任务标题',
            height:'250',
            table: {
                url: '/office_automation/public/index/mission_c/selectIndex'
                ,cols: [[
                    { type: 'radio' },
                    { field: 'mission_id', title: '任务号' },
                    { field: 'mission_title', title: '任务标题' },
                    { field: 'assignee_name', title: '处理人' }
                ]]
            },
            done: function (elem, data) {
                var result = data.data;
                if(result.length != 0) {
                    if(elem.val() == '') {
                        elem.val(result[0].mission_id);
                    } else {
                        var userList = elem.val().split('；');
                        if(!userList.includes(result[0].mission_id + '')) {
                            userList.push(result[0].mission_id);
                            elem.val(userList.join('；'));
                        }
                    }
                }
            }
        });
    });
}

// 上传附件
function uploadAttachment() {
    layui.use(['upload'], function () {
        var $ = layui.$,
            upload = layui.upload;
        var fileListView = $('#fileList')
            ,uploadListIns = upload.render({
            elem: '#attachment'
            ,url: '/office_automation/public/attachment'
            ,auto: false
            ,multiple: true
            ,size: 102400            // 单位 KB，最大 100MB
            ,accept: 'file'
            ,bindAction: '#start_upload'
            ,choose: function(obj){
                if($('#fileList').children().length > 9) {
                    return layer.msg('最多上传十个附件！');
                }
                var files = this.files = obj.pushFile();         // 将每次选择的文件追加到文件队列

                $("#start_upload").removeAttr("disabled");
                // 预读本地文件
                obj.preview(function(index, file, result){          // 分别是文件索引、文件对象、文件base64编码
                    var tr = $(['<tr id="upload-'+ index +'">'
                        ,'<td>'+ file.name +'</td>'
                        ,'<td>'+ (file.size/1024).toFixed(1) +'kb</td>'
                        ,'<td>等待上传</td>'
                        ,'<td>'
                        ,'<button class="layui-btn layui-btn-xs layui-hide reload">重传</button>'
                        ,'<button class="layui-btn layui-btn-xs layui-btn-danger delete">删除</button>'
                        ,'</td>'
                        ,'</tr>'].join(''));

                    //单个重传
                    tr.find('.reload').on('click', function(){
                        obj.upload(index, file);
                    });

                    //删除
                    tr.find('.delete').on('click', function(){
                        delete files[index];            // 删除对应的文件
                        tr.remove();
                        uploadListIns.config.elem.next()[0].value = '';         // 清空 input file 值，以免删除后出现同名文件不可选
                        if(!files) {
                            $("#start_upload").attr("disabled", true);
                        }
                    });
                    fileListView.append(tr);
                });
            }
            ,before: function(obj){
                layer.load();            // 取消上传后仍会触发 before
            }
            ,done: function(res, index, upload){
                if(res.code == 0){
                    // 写入附件 id
                    if($('#attachment_list').val() == '') {
                        $('#attachment_list').val(res.data.id);
                    } else {
                        var ids = $('#attachment_list').val().split(';');
                        if(!ids.includes(res.data.id)) {
                            ids.push(res.data.id);
                        }
                        $('#attachment_list').val(ids.join(';'));
                    }

                    var tr = fileListView.find('tr#upload-'+ index)
                        ,tds = tr.children();
                    tds.eq(2).html('<span style="color: #5FB878;">上传成功</span>');
                    tds.eq(3).html('');
                    delete this.files[index];            // 删除文件队列已经上传成功的文件
                } else {
                    console.log('上传失败：' + res.msg + `(${index})`);
                    this.error(index, upload);
                }
            }
            ,allDone: function(obj){
                layer.closeAll('loading');
                layer.msg('上传完成！');
                $("#start_upload").attr("disabled", true);
                console.log('上传完成！共上传' + obj.total + '个文件，成功文件数：' + obj.successful +'，失败文件数：' + obj.aborted);
            }
            ,error: function(index, upload){            // 分别为当前文件的索引、重新上传的方法
                var tr = fileListView.find('tr#upload-'+ index)
                    ,tds = tr.children();
                tds.eq(2).html('<span style="color: #FF5722;">上传失败</span>');
                tds.eq(3).find('.reload').removeClass('layui-hide');           // 显示重传
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
            content: '../mission/read.html?id=' + missionId,
            shade: 0.2,
            maxmin:true,
            shadeClose: true,
            area: ['100%', '100%'],
        });
    });
}