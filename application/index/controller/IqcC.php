<?php
/**
 * Created by PhpStorm.
 * User: Link
 * Date: 2020/11/3
 * Time: 9:42
 */

namespace app\index\controller;


use app\common\Result;
use app\index\model\Iqc;
use app\index\model\IqcMaterial;
use app\index\model\IqcType;
use think\Session;

class IqcC
{
    /**
     * 根据物料编码获取物料名字
     * @return array
     * @throws \think\exception\DbException
     */
    public function getMaterialNameByCode(){
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
        $iqcList = $iqc -> where("material_code","like" ,"$preCode%")
                        -> field("material_code")
                        -> select();
        return Result::returnResult(Result::SUCCESS,$iqcList);
    }

    /**
     * 保存发起的iqc缺陷
     */
    public function saveIQC(){
        $describe     = $_POST["describe"];
        $batchNum     = $_POST["batchNum"];
        $materialCode = $_POST["materialCode"];
        $userInfo = Session::get('info');
        $userId = $userInfo["user_id"];
        $iqc = new Iqc([
            "proposer_id" => $userId,
            "code"        => $materialCode,
            "batch_num"   => $batchNum,
            "describe"    => $describe,
            "create_time" => date('Y-m-d H:i:s', time())
        ]);
        $resCount = $iqc -> save();
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
                        -> field("id,proposer_id,code,batch_num,describe,create_time")
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
     * 根据iqc编码查询缺陷信息
     * @param $page
     * @param $limit
     * @param $matCode
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getIqcMatOfCode($page, $limit, $matCode){
        $iqc = new Iqc();
        if($matCode != ""){
            $iqc -> where("code",$matCode);
        }
        $iqcList = $iqc -> where("status",1)
                        -> field("id,proposer_id,code,batch_num,describe,create_time")
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
     * 根据id号查询详细缺陷信息
     */
    public function getIqcMatInfoOfId(){
        $iqcId = $_GET["id"];
        $iqc = new Iqc();
        $iqcInfo = $iqc -> where("status",1)
                        -> where("id",$iqcId)
                        -> field("id,proposer_id,code,batch_num,describe,create_time")
                        -> order("id","desc")
                        -> find();
        $iqcInfo -> proposer_name = $iqcInfo -> proposer -> user_name;
        $iqcInfo -> name = $iqcInfo -> material -> material_name;
        unset($iqcInfo -> proposer,$iqcInfo -> material);
        return Result::returnResult(Result::SUCCESS,$iqcInfo);
    }

}