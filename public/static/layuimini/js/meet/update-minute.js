


layui.use(['form', 'layedit', 'laydate' ,'upload','table'], function () {
    var   form    = layui.form
        , layer   = layui.layer
        , layedit = layui.layedit
        , laydate = layui.laydate
        , upload  = layui.upload
        , table   = layui.table
        , tableSelect = layui.tableSelect;

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
                    $('input').css("pointer-events", "none");
                    $('textarea').css("pointer-events", "none");
                    $('button').css("pointer-events", "none");
                    // $('#fileList button').remove();
                    $('.layui-upload-drag').css("pointer-events", "none");
                    $('#missionTable').remove();
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
                var data = res.data;
                var attendArray = data.minuteAttends;
                var attendedArray = data.minuteAttendeds;
                var missionArray = data.minuteMission;                //实际的任务
                var minuteTempMission = data.minuteTempMission;       //临时保存的会议任务
                var newMissionArray = "";
                if(minuteTempMission.length > 0){
                    newMissionArray = minuteTempMission[0].new_temp_list;  //任务清单
                }
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
                form.render("select");
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

    laydate.render({
        elem: '#dateTime'
    });

    /**
     * 选择已到会员工
     */
    tableSelect.render({
        elem: '#attended-user',	//定义输入框input对象 必填
        checkedKey: 'user_id', //表格的唯一建值，非常重要，影响到选中状态 必填
        searchKey: 'keyword',	//搜索输入框的name值 默认keyword
        searchList: [
            {searchKey: 'keyword', searchPlaceholder: '员工名字/部门'},  //搜索条件1
        ],
        table: {	//定义表格参数，与LAYUI的TABLE模块一致，只是无需再定义表格elem
            url:'/office_automation/public/index.php/index/minute_c/getAllUsers?type=1&minuteId='+minute_id,
            cols: [[
                {type: "checkbox"},
                {field: 'user_id', title: '员工工号'},
                {field: 'user_name', title: '姓名'},
                {field: 'department_name', title: '部门'},
            ]]
        },
        done: function (elem, data) {
            var NEWJSON = []
            layui.each(data.data, function (index, item) {
                NEWJSON.push(item.user_name);
                attended.push(item.user_id);
            })
            elem.val(NEWJSON.join(","))
        }
    });

    /**
     * 选择新添需要到会员工
     */
    tableSelect.render({
        elem: '#new-attend-users',
        checkedKey: 'user_id',
        searchKey: 'keyword',
        searchList: [
            {searchKey: 'keyword', searchPlaceholder: '员工名字/部门'},
        ],
        table: {
            url:'/office_automation/public/index.php/index/minute_c/getAllUsers?type=2&minuteId='+minute_id,
            cols: [[
                {type : "checkbox"},
                {field: 'user_id', title: '员工工号'},
                {field: 'user_name', title: '姓名'},
                {field: 'department_name', title: '部门'},
            ]]
        },
        done: function (elem, data) {
            var NEWJSON = [];
            layui.each(data.data, function (index, item) {
                NEWJSON.push(item.user_name);
                newAttended.push(item.user_id);
            })
            elem.val(NEWJSON.join(","))
        }
    });

    /**
     * 选择基本任务清单
     */
    tableSelect.render({
        elem: '#add-mission',
        checkedKey: 'mission_id',
        searchKey: 'keyword',
        searchList: [
            {searchKey: 'keyword', searchPlaceholder: '任务标题/任务ID'},  //搜索条件
        ],
        table: {
            url:'/office_automation/public/index/mission_c/selectIndex?type=1',
            cols: [[
                {type: "checkbox"},
                {field: 'mission_id', title: '任务号'},
                {field: 'mission_title', title: '任务标题'},
                {field: 'assignee_name', title: '负责人'},
            ]]
        },
        done: function (elem, data) {
            var NEWJSON = [];
            layui.each(data.data, function (index, item) {
                NEWJSON.push(item.mission_id);
            });
            newMission = NEWJSON;
            elem.val(NEWJSON.join(","))
        }
    });

    //文件上传
    var fileListView = $('#fileList')
        ,uploadListIns = upload.render({
        elem: '#attachment'
        ,url: '/office_automation/public/attachment'
        ,auto: false
        ,multiple: true
        ,number: 3          // 同时可上传的文件数量
        ,size: 20480            // 单位 KB，最大 20MB
        ,accept: 'file'
        ,bindAction: '#start_upload'
        ,choose: function(obj){
            if($('#fileList').children().length > 2) {
                return layer.msg('最多上传三个附件！');
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
                uploadList.push(res.data.id);
                var tr = fileListView.find('tr#upload-'+ index)
                    ,tds = tr.children();
                tds.eq(3).html('<span style="color: #5FB878;">上传成功</span>');
                tds.eq(4).html('');
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

    //自定义验证规则
    form.verify({
        title: function (value) {
            if (value.length < 5) {
                return '标题至少得5个字符啊';
            }
        }
        , pass: [
            /^[\S]{6,12}$/
            , '密码必须6到12位，且不能出现空格'
        ]
        , content: function (value) {
            layedit.sync(editIndex);
        }
    });

    //展示会议纪要任务表已有数据
    table.render({
        elem: '#minute-table'
        ,cols: [[ //标题栏
            {field: 'mission_id', title: 'ID', width: 200}
            ,{field: 'mission_title', title: '标题', width: 200}
            ,{field: 'assignee_name', title: '责任人', Width: 200}
            ,{field: 'finish_date',title: '完成日期', Width: 200}
            ,{field: 'process_note', title: '最近处理信息', width: 200}
            ,{field: 'status', title: '进度', width: 200}
        ]]
        ,data: []
        ,even: true
    });

    /**
     * 添加一条会议任务
     */
    $("#addEvent").on("click",function () {
        var $missionInfo = "<tr class='missionItem'>\n" +
            "                        <td><input type=\"text\" name=\"title\" lay-verify=\"required\" placeholder=\"请输入标题\" autocomplete=\"off\" class=\"layui-input title\"></td>\n" +
            "                        <td>\n" +
            "                            <select class=\"departmentSelect\" name=\"departmentSelect\" lay-verify=\"required\" lay-filter=\"departmentSelect\">\n" +
            "                                <option value=\"\">--选择部门--</option>\n" +
            departmentInfo +
            "                            </select>\n" +
            "                        </td>\n" +
            "                        <td>\n" +
            "                            <select class=\"userSelect responsible\" name=\"userSelect\" lay-verify=\"required\" lay-filter=\"userSelect\">\n" +
            "                                <option value=\"\">--请先选择部门--</option>\n" +
            "                            </select>\n" +
            "                        </td>\n" +
            "                        <td><input type=\"date\" name=\"dateTime\" autocomplete=\"off\" lay-verify=\"required\" class=\"layui-input finish-date\"></td>\n" +
            "                        <td><input type=\"text\" name=\"title\" placeholder=\"请输入任务描述\" autocomplete=\"off\" lay-verify=\"required\" class=\"layui-input describe\"></td>\n" +
            "                        <td> <a class=\"layui-btn layui-btn-xs layui-btn-danger data-count-delete deleteMission\">删除</a></td>\n" +
            "                    </tr>";
        $("#addMissionTable").append($missionInfo);
        form.render();
    });

    /**
     * 监听会议类型查询
     */
    form.on('select(departmentSelect)',function(data){
        var department_id = data.value;
        var $userSelect = $(this).parent().parent().parent().next().children("select");
        $.ajax({
            url: "/office_automation/public/index.php/index/user_c/getUserByDepartment",
            type:'get',
            timeout: 1000,
            data: {
                departmentId : department_id,
            },
            success: function(res){
                var userArray = res.data;
                var userOptions = "";
                $userSelect.empty();//先清空子节点
                if(userArray == null){
                    userOptions = "<option value=''>" + "--请先选择部门--" + "</option>";
                    $userSelect.append(userOptions);
                    form.render();
                    return;
                }
                userOptions += "<option value=''>" + "--选择负责人--" + "</option>";
                for (var i = 0; i < userArray.length; i++){
                    userOptions += "<option value='" + userArray[i]["user_id"] + "'>" + userArray[i]["user_name"] + "</option>";
                }
                $userSelect.append(userOptions);
                form.render();
            },
            error: function(data){
            }
        });
        return false;
    });

    /**
     * 删除某一条会议任务
     */
    $("#addMissionTable tbody").on("click",".deleteMission", function() {
        $(this).parent().parent().remove();
    });

    //监听提交
    form.on('submit(save)', function (data) {
        var minuteResolution  = $("#minute-resolution").val();
        var minuteContext     = $("#minute-context").val();
        var minuteMission     = getMissionInfo();
        $.ajax({
            url: "/office_automation/public/index.php/index/minute_c/updateMinute",
            type:'post',
            timeout: 2000,
            data: {
                minuteId        : minute_id,
                attendList      : attended,         //已到会人员（数组）
                newAttended     : newAttended,      //新增应到会人
                newMission      : newMission,       //关联新任务
                minuteResolution: minuteResolution, //会议决议
                minuteContext   : minuteContext,    //会议记录
                minuteMission   : minuteMission,    //新会议任务（数组）
                uploadList      : uploadList        //附件（数组）
            },
            success: function(res){
                layer.alert('会议修改成功！', {title: '提示'},
                    function (index) {
                        layer.close(index);
                        location.reload();
                    }
                );
            },
            error: function(data){
            }
        });
        return false;
    });

    //重置
    $("#refresh").on("click",function(){
        location.reload();
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

});
