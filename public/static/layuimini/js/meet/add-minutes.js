layui.use(['form', 'layedit', 'laydate' ,'upload','miniTab'], function () {
    var form = layui.form
        , layer = layui.layer
        , layedit = layui.layedit
        , laydate = layui.laydate
        , upload = layui.upload
        , miniTab = layui.miniTab
        , tableSelect = layui.tableSelect,
        $ = layui.$;

    var userIdList = []; //已经选择的应到会议员工id数组
    var uploadList = [];
    var hostId;  //根绝这个变量让会议发起人必须在应到会议名单中
    var hostName;

    /**
     * 获取所有部门信息和会议类型信息
     */
    $.ajax({
        url: "/office_automation/public/index.php/index/minute_c/getAddInfo",
        type:'post',
        timeout: 2000,
        data: {},
        success: function(res){
            var departmentName = res["data"]["departmentName"];
            hostName = res["data"]["hostName"];
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
                var $option = "<option value='" + projectArray[i]["project_id"] + "'>" + projectArray[i]["project_code"] + "</option>";
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

    //进入页面先判断是否有临时保存的会议信息
    $.ajax({
        url: "/office_automation/public/index.php/index/minute_c/hasNewTempMinute",
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
                    $("#select-project-code").val(data.project_code);
                    $("#date").val(data.minute_date);
                    $("#time").val(data.minute_time);
                    $("#place").val(data.place);
                    $("#minute-resolution").val(data.resolution);
                    $("#minute-context").val(data.record);
                    var attend_users = data.minuteNewAttends;
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
            url: "/office_automation/public/index.php/index/minute_c/saveNewTemp",
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


    //选择应到人员
    tableSelect.render({
        elem: '#list-users',	//定义输入框input对象 必填
        checkedKey: 'user_id', //表格的唯一建值，非常重要，影响到选中状态 必填
        searchKey: 'keyword',	//搜索输入框的name值 默认keyword
        searchPlaceholder: '员工名字 / 部门',
        table: {	//定义表格参数，与LAYUI的TABLE模块一致，只是无需再定义表格elem
            url:'/office_automation/public/index.php/index/minute_c/getAllUsers?type=0',
            cols: [[
                {type: "checkbox"},
                {field: 'user_id', title: '员工工号'},
                {field: 'user_name', title: '姓名'},
                {field: 'department_name', title: '部门'},
            ]],
            limits: [10, 15, 20, 25, 50, 100],  //选择一次显示多少行
            limit: 10,  //默认显示多少行数据
        },
        done: function (elem, data) {
            var NEWJSON = [];                       //显示给用户看的员工名字
            userIdList.splice(0,userIdList.length); //清空数组
            layui.each(data.data, function (index, item) {
                NEWJSON.push(item.user_name);
                userIdList.push(item.user_id);
            });
            //判断应到列表中是否存在会议发起人ID，若没有则添加
            if($.inArray(hostId , userIdList) === -1){
                userIdList.push(hostId);
            }
            //判断应到列表中是否存在会议发起人姓名，若没有则添加
            if($.inArray(hostName , NEWJSON) === -1){
                NEWJSON.push(hostName);
            }
            elem.val(NEWJSON.join("，"))
        }
    });

    //日期
    laydate.render({
        elem: '#date'
        ,value: new Date()
    });
    //时间
    laydate.render({
        elem: '#time'
        ,type: 'time'
        ,format: 'H点m分'
        ,value: new Date()
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
                    delete files[index]; //删除对应的文件
                    tr.remove();
                    uploadListIns.config.elem.next()[0].value = '';         // 清空 input file 值，以免删除后出现同名文件不可选
                    if($.isEmptyObject(files)) {
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
                uploadList.push(res.data.id);
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

    //创建一个编辑器
    var editIndex = layedit.build('LAY_demo_editor');

    //自定义验证规则
    form.verify({
        title: function (value) {
            if (value.length < 1) {
                return '请填写必要信息';
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

    //监听会议类型
    form.on('select(minute_type)',function(data){
        //根据选择的会议类型，更改不同的会议记录
        var simple_type = "---会议目的---\n" +
            "1.\n" +
            "---会议议题---\n" +
            "1.\n" +
            "--会前工作---\n" +
            "1.\n" +
            "--会议过程记录--\n" +
            "1.\n" +
            "2.";
        var review_type = "---评审内容---\n" +
            "1.\n" +
            "---资料类型---\n" +
            "（可选项：设计开发文档、技术文档、其他（要写出其他的具体内容））\n" +
            "--会议时间/会签截止时间---\n" +
            "\n" +
            "--评审记录--\n" +
            "1.\n" +
            "提出人：\n" +
            "问题：\n" +
            "重要程度：\n" +
            "处理方式：\n" +
            "处理人：";
        var ECR_type = "---对各业务方向的影响（各方向负责人写）---\n" +
            "系统（牛顿） ：\n" +
            "液路（牛顿）:\n" +
            "硬件（尚添）：\n" +
            "机械（肖清文）：\n" +
            "软件（崔刚）：\n" +
            "设计转换（何战仓）：\n" +
            "试剂（TBD）：\n" +
            "其他：\n" +
            "\n" +
            "---对各业务方向的影响（申请人自己写，非必填）---\n" +
            "系统： \n" +
            "液路：\n" +
            "硬件：\n" +
            "机械：\n" +
            "下位机软件：\n" +
            "上位机软件：\n" +
            "设计转换：\n" +
            "试剂：\n" +
            "其他：";
        var minute_resolution = "--初步评审结论：";
        switch (data.value) {
            case "0":
                $("#minute-context").val(simple_type);
                $("#minute-resolution").val("");
                break;
            case "1":
                $("#minute-context").val(review_type);
                $("#minute-resolution").val(minute_resolution);
                break;
            case "2":
                $("#minute-context").val(review_type);
                $("#minute-resolution").val(minute_resolution);
                break;
            case "3":
                $("#minute-context").val(ECR_type);
                $("#minute-resolution").val(minute_resolution);
                break;
        }
        return false;
    });

    //确认提交
    form.on('submit(save)', function (data) {
        var minute_info             = data.field;
        //判断应到列表中是否存在会议发起人，若没有则添加
        if($.inArray(hostId , userIdList) === -1){
            userIdList.push(hostId);
        }
        minute_info.attend_users    = userIdList;
        minute_info.file            = uploadList;
        layer.confirm('确定提交？', {icon: 3, title:'提示'}, function(index){
            layer.close(index);
            var loadingIndex = layer.load(2);
            $.ajax({
                url: "/office_automation/public/index.php/index/minute_c/saveMinute",
                type:'post',
                timeout: 1000,
                data: minute_info,
                success: function(res){
                    layer.close(loadingIndex);
                    layer.alert('会议发起成功！', {title: '提示'},
                        function (index) {
                            layer.close(index);
                            location.reload();
                            miniTab.openNewTabByIframe({
                                href:"page/meet/minutes.html",
                                title:"纪要列表",
                            });
                        }
                    );
                },
                error: function(data){
                }
            });
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









