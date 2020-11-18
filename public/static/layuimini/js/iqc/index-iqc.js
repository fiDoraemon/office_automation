showZoomImg('.zoomImg', 'img');
showZoomImg('.zoomImgshow', 'text');

layui.use(['form', 'table'], function () {
    var $ = layui.jquery,
        form = layui.form,
        table = layui.table;

    $.ajax({
        url: "/office_automation/public/index.php/index/iqc_c/getIqcType",
        type:'get',
        success: function(res){
            var projectArray = res.data;
            for (var i = 0; i < projectArray.length; i++){
                var $option = "<option value='" + projectArray[i]["iqc_type"] + "'>" + projectArray[i]["iqc_type"] + "</option>";
                $("#select-iqc-type").append($option);
            }
            form.render('select');
        },
        error: function(data){
        }
    });

    table.render({
        elem: '#currentTableId',
        url: '/office_automation/public/index.php/index/iqc_c/getIqcMatOfCode',
        cols: [[
            {field: 'code', title: '编码', width: 125},
            {field: 'batch_num', title: '批号', width: 95},
            {field: 'supplier', title: '供应商', width: 100},
            {field: 'proposer_name', title: '提交者', width: 80},
            {field: 'create_time', title: '提交时间', width: 160},
            {field: 'describe', title: '描述', width: 300, templet: function(d){
                    return '<textarea class="context" disabled>' + d.describe + '</textarea>';
                }
            },
            {field: 'measures', title: '解决措施', width: 300, templet: function(d){
                    return '<textarea class="context" disabled>' + d.measures + '</textarea>';
                }
            },
            {field: 'picList', title: '缺陷图片',height: 200,templet: function(d){
                    var picArr = d.picList;
                    var $picList = '<div class="zoomImgBox">';
                    for(let i = 0;i< picArr.length; i++){
                        let name = picArr[i]["source_name"];
                        let path = picArr[i]["save_path"];
                        $picList += '<a href="#"><img class="zoomImg" data-caption="' + name + '" src="/Office_Automation/public/upload/iqc-pic/'+ path +'" alt="'+ name +'"/></a>';
                    }
                    $picList += '</div>';
                    return $picList;
                }
            },
        ]],
        page: false,
        // skin: 'line'
    });

    /**
     * 监听搜索按钮操作
     */
    form.on('submit(data-search-btn)', function (data) {
        var result = data.field;
        //执行搜索重载
        table.reload('currentTableId', {
            url:"/office_automation/public/index.php/index/iqc_c/getIqcMatOfCode",
            where: {
                matCode: result.keyword
            }
        }, 'data');
        return false;
    });

    $("#material-code").bind("input propertychange", function() {
        var matCode = $("#material-code").val();
        if(matCode.length !== 13){
            $("#iqc-name-tips").html("无此编码&emsp;"+"<i class='layui-icon layui-icon-next'></i>");
            return;
        }
        $.ajax({
            url: "/office_automation/public/index.php/index/iqc_c/getMaterialNameByCode",
            type: "get",
            data:{
                matCode : matCode
            },
            success: function(res){
                if(res.code === 0){
                    $("#iqc-name-tips").html(res.data +"&emsp;<i class='layui-icon layui-icon-next'></i>");
                }else{
                    $("#iqc-name-tips").html("无此编码&emsp;<i class='layui-icon layui-icon-next'></i>");
                }
            },
            error: function(res){}
        });
    });

    /**
     * 监听物料编号前三位数(物料类型)
     */
    form.on('select(pre_code)',function(data){
        $.ajax({
            url: "/office_automation/public/index.php/index/iqc_c/getIqcCodeOfPre",
            type: "get",
            data:{
                preCode : data.value
            },
            success: function(res){
                if(res.code === 0){
                    $("#select-iqc-code").empty();
                    var codeArray = res.data;
                    var $option = "<option value=\"\" selected>--请选择--</option>";
                    for (var i = 0; i < codeArray.length; i++){
                        $option += "<option value='" + codeArray[i]["material_code"] + "'>" + codeArray[i]["material_code"] + "</option>";
                    }
                    $("#select-iqc-code").append($option);
                    form.render('select');
                }
            },
            error: function(res){

            }
        });
        table.reload('currentTableId', {
            url:"/office_automation/public/index.php/index/iqc_c/getIqcMatInfoOfType",
            where: {
                matCode: data.value,
            }
        }, 'data');
        return false;
    });

    /**
     * 监听物料编码
     */
    form.on('select(material_code)',function(data){
        $.ajax({
            url: "/office_automation/public/index.php/index/iqc_c/getMaterialNameByCode",
            type: "get",
            data:{
                matCode : $("#select-iqc-code").val()
            },
            success: function(res){
                if(res.code === 0){
                    $("#iqc-name-tips").html("<i class='layui-icon layui-icon-prev'></i>&emsp;" + res.data);
                }else{
                    $("#iqc-name-tips").html("");
                }
            },
            error: function(res){}
        });
        table.reload('currentTableId', {
            url:"/office_automation/public/index.php/index/iqc_c/getIqcMatOfCode",
            where: {
                matCode: data.value,
            }
        }, 'data');
        return false;
    });

    // table.on('tool(currentTableFilter)', function (obj) {
    //     var data = obj.data;
    //     var IQC_id = data["id"];
    //     if (obj.event === 'edit') {
    //         var index = layer.open({
    //             title: '缺陷详情',
    //             type: 2,
    //             shade: 0.2,
    //             maxmin:true,
    //             shadeClose: true,
    //             area: ['100%', '100%'],
    //             content: 'detailed-iqc.html?IQC_id=' + IQC_id,
    //         });
    //         $(window).on("resize", function () {
    //             layer.full(index);
    //         });
    //         return false;
    //     }
    // });
});