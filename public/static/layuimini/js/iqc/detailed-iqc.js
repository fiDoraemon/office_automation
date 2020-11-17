layui.use(['form','miniTab','upload'], function () {
    var $ = layui.jquery;

    var IQC_id = getQueryVariable('IQC_id');

    $.ajax({
        url:"/office_automation/public/index.php/index/iqc_c/getIqcMatInfoOfId",
        type:"get",
        data:{
            id: IQC_id
        },
        success: function(res){
            console.log(res);
            if(res.code === 0){
                var data = res.data.info;
                $("#material-code").val(data.code);
                $("#material-name").val(data.name);
                $("#proposer").val(data.proposer_name);
                $("#create-time").val(data.create_time);
                $("#batch-num").val(data.batch_num);
                $("#describe").val(data.describe);
                var picArr = res.data.picList;
                var $pic = "";
                for (let i = 0; i < picArr.length; i++) {
                    let name = picArr[i]["source_name"];
                    let path = picArr[i]["save_path"];
                    console.log(path)
                    $pic +=  '<img src="/Office_Automation/public/upload/iqc-pic/'+ path +'" alt="'+ name +'"/>';
                }
                $("#mat-pic").append($pic);
            }
        },
        error: function(res){

        }
    });
});