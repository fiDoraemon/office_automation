<?php
/**
 * Created by PhpStorm.
 * User: Link
 * Date: 2020/11/3
 * Time: 9:42
 */

namespace app\index\controller;


use app\common\Result;
use app\index\model\Attachment;
use app\index\model\Iqc;
use app\index\model\IqcMaterial;
use app\index\model\IqcType;
use think\Db;
use think\Session;

class IqcC
{
    /**
     * 根据物料编码获取物料名字
     * @return array
     * @throws \think\exception\DbException
     */
    public function getMaterialNameByCode(){
        header('Access-Control-Allow-Origin: *');
        $matCode = $_GET["matCode"];
        $iqc = IqcMaterial::get(['material_code' => $matCode]);
        if($iqc != null){
            return Result::returnResult(Result::SUCCESS, $iqc -> material_name);
        }
        return Result::returnResult(Result::ERROR);
    }

    /**
     * 获取所有iqc类型
     */
    public function getIqcType(){
        $iqcType = new IqcType();
        $iqcTypeList = $iqcType -> field("iqc_type") -> select();
        return Result::returnResult(Result::SUCCESS,$iqcTypeList);
    }

    /**
     * 根据物料编码前三位，获取所有对应的物料编码
     */
    public function getIqcCodeOfPre(){
        $preCode = $_GET["preCode"];
        $iqc = new IqcMaterial();
        $iqcCodeList = $iqc -> where("material_code","like" ,"$preCode%")
                        -> field("material_code")
                        -> order("material_code")
                        -> select();
        return Result::returnResult(Result::SUCCESS,$iqcCodeList);
    }


    /**
     * 保存发起的iqc缺陷
     * @throws \think\exception\DbException
     */
    public function saveIQC(){
        $describe     = $_POST["describe"];
        $batchNum     = $_POST["batchNum"];
        $supplier     = $_POST["supplier"];
        $materialCode = $_POST["materialCode"];
        $measures     = $_POST["measures"];
        $fileIdList   = input('post.file/a');
        $userInfo = Session::get('info');
        $userId = $userInfo["user_id"];
        $iqc = new Iqc([
            "proposer_id" => $userId,
            "code"        => $materialCode,
            "batch_num"   => $batchNum,
            "measures"    =>$measures,
            "describe"    => $describe,
            "supplier"    =>$supplier,
            "create_time" => date('Y-m-d H:i:s', time())
        ]);
        $resCount = $iqc -> save();
        foreach ($fileIdList as $fileId){
            $att = Attachment::get(['attachment_id' => $fileId]);
            $att -> attachment_type = "iqc";
            $att -> related_id = $iqc -> id;
            $att -> save();
        }
        if($resCount > 0){
            return Result::returnResult(Result::SUCCESS);
        }
        return Result::returnResult(Result::ERROR);
    }

    /**
     * 获取所有iqc缺陷信息
     */
    public function getAllIqcMatInfo($limit = 15, $page = 1){
        $iqc = new Iqc();
        $iqcList = $iqc -> where("status",1)
                        -> field("id,proposer_id,code,batch_num,supplier,describe,measures,create_time")
                        -> order("id","desc")
                        -> page($page, $limit)
                        -> select();
        foreach ($iqcList as $iqcItem){
            $iqcItem -> proposer_name = $iqcItem -> proposer -> user_name;
            $iqcItem -> name = $iqcItem -> material -> material_name;
            unset($iqcItem -> proposer,$iqcItem -> material);
        }
        return Result::returnResult(Result::SUCCESS,$iqcList);
    }

    /**
     * 根据物料类型查询物料缺陷信息（物料编码前三位数）
     */
    public function getIqcMatInfoOfType(){
        $preCode = $_GET["matCode"];
        if($preCode == null){
            return Result::returnResult(Result::SUCCESS);
        }
        $iqc = new Iqc();
        $iqcList = $iqc -> where("status",1)
                        -> where("code","like","$preCode%")
                        -> field("id,proposer_id,code,batch_num,supplier,describe,measures,create_time")
                        -> order("code")
                        -> order("id desc")
                        -> select();
        foreach ($iqcList as $iqcInfo){
            $iqcInfo -> proposer_name = $iqcInfo -> proposer -> user_name;
            $iqcInfo -> name          = $iqcInfo -> material -> material_name;
            unset($iqcInfo -> proposer,$iqcInfo -> material);
            $attach = new Attachment();
            $picList = $attach -> where("attachment_type", "iqc")
                               -> where("related_id",$iqcInfo -> id)
                               -> field("source_name,save_path")
                               -> select();
            $iqcInfo -> picList = $picList;
        }
        return Result::returnResult(Result::SUCCESS,$iqcList);
    }

    /**
     * 根据iqc编码查询缺陷信息
     * @param $matCode
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getIqcMatOfCode($matCode = ""){
        if($matCode == ""){
            return Result::returnResult(Result::SUCCESS);
        }
        $iqc = new Iqc();
        $iqcList = $iqc -> where("status",1)
                        -> where("code",$matCode)
                        -> field("id,proposer_id,code,batch_num,supplier,describe,measures,create_time")
                        -> order("id desc")
                        -> select();
        foreach ($iqcList as $iqcInfo){
            $iqcInfo -> proposer_name = $iqcInfo -> proposer -> user_name;
            $iqcInfo -> name          = $iqcInfo -> material -> material_name;
            unset($iqcInfo -> proposer,$iqcInfo -> material);
            $attach = new Attachment();
            $picList = $attach -> where("attachment_type", "iqc")
                               -> where("related_id",$iqcInfo -> id)
                               -> field("source_name,save_path")
                               -> select();
            $iqcInfo -> picList = $picList;
        }
        return Result::returnResult(Result::SUCCESS,$iqcList);
    }

    /**
     * 同时接受iqc信息和缺陷图片（多张）
     * iqc上传缺陷移动端接口
     */
    public function saveIqcInfoAndPic(){
        header('Access-Control-Allow-Origin: *');
        //开始事务
        $describe     = $_POST["describe"];
        $batchNum     = $_POST["batchNum"];
        $supplier     = $_POST["supplier"];
        $materialCode = $_POST["materialCode"];
        $userId       = $_POST["userId"];
        $measures     = $_POST["measures"];
        $fileIdList = request() -> file('fileList');
        $iqc = new Iqc([
            "proposer_id" => $userId,
            "code"        => $materialCode,
            "batch_num"   => $batchNum,
            "measures"    => $measures,
            "describe"    => $describe,
            "supplier"    => $supplier,
            "create_time" => date('Y-m-d H:i:s', time())
        ]);
        $resCount = $iqc -> save();
        foreach ($fileIdList as $imgFile){
            $info = $imgFile -> move(ROOT_PATH . 'public' . DS . 'upload'. DS .'iqc-pic');
            $fileInfo = $imgFile -> getInfo();
            $attachment = new Attachment([
                'source_name'     => $fileInfo["name"],
                'storage_name'    => $info -> getFilename(),
                'uploader_id'     => $userId,
                'file_size'       => $info -> getSize(),
                'save_path'       => $info -> getSaveName(),
                'attachment_type' => "iqc",
                'related_id'      => $iqc -> id
            ]);
            $attachment->save();
        }
        if($resCount > 0){
            return Result::returnResult(Result::SUCCESS);
        }
        return Result::returnResult(Result::ERROR);
    }

    /**
     * 根据id号查询详细缺陷信息
     */
    public function getIqcMatInfoOfId(){
        $iqcId = $_GET["id"];
        $iqc = new Iqc();
        $iqcInfo = $iqc -> where("status",1)
                        -> where("id",$iqcId)
                        -> field("id,proposer_id,code,batch_num,supplier,describe,measures,create_time")
                        -> order("id","desc")
                        -> find();
        $iqcInfo -> proposer_name = $iqcInfo -> proposer -> user_name;
        $iqcInfo -> name = $iqcInfo -> material -> material_name;
        unset($iqcInfo -> proposer,$iqcInfo -> material);
        $attach = new Attachment();
        $picList = $attach -> where("attachment_type", "iqc")
                           -> where("related_id",$iqcInfo -> id)
                           -> field("source_name,save_path")
                           -> select();
        $matInfo = ["info"    => $iqcInfo,
                    "picList" => $picList];
        return Result::returnResult(Result::SUCCESS,$matInfo);
    }

    //定义一个方法名upload_img，和view/TestImage文件夹下面的upload_img同名，提交信息时匹配文件
    public function uploadImg(){
        //判断是否是post 方法提交的
        if(request()->isPost()){
            $data = input('post.');
            //处理图片上传
            //提交时在浏览器存储的临时文件名称
            if($_FILES['fileList']['tmp_name']){
                $data['image'] = $this -> upload();
            }
            return $data['image'];
        }
        return Result::returnResult(Result::ERROR);
    }

    //上传图片函数
    private function upload(){
        // 获取表单上传的文件，例如上传了一张图片
        $file = request() -> file('fileList');
        if($file){
            //将传入的图片移动到框架应用根目录/public/uploads/ 目录下，ROOT_PATH是根目录下，DS是代表斜杠 /
            $info = $file -> move(ROOT_PATH . 'public' . DS . 'upload'. DS .'iqc-pic');
            if($info){
                // 插入附件信息
                $fileInfo = $file -> getInfo();
                $userId = Session::get("info")["user_id"];
                $attachment = new Attachment([
                    'source_name'  => $fileInfo["name"],
                    'storage_name' => $info -> getFilename(),
                    'uploader_id'  => $userId,
                    'file_size'    => $info -> getSize(),
                    'save_path'    => $info -> getSaveName()
                ]);
                $attachment->save();
                return $attachment -> attachment_id;
            }else{
                // 上传失败获取错误信息
                return Result::returnResult(Result::ERROR);
            }
        }
    }


}