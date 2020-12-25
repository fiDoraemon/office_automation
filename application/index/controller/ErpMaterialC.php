<?php
/**
 * Created by PhpStorm.
 * User: TZX
 * Date: 2020/12/24
 * Time: 13:35
 */

namespace app\index\controller;

use app\common\Result;
use app\index\model\ErpInventory;
use app\index\model\ErpMaterial;
use think\Db;

/**
 * ERP物料控制器
 * Class ErpMaterialC
 * @package app\index\controller
 */
class ErpMaterialC
{
    /**
     * 显示ERP物料列表
     */
    public function index($page = 1, $limit = 10) {
        $erpMaterial = new ErpMaterial();
        $materialCode = input('get.materialCode');          // 物料编码
        $keyword = input('get.keyword');            // 物料名称关键词
        // 获取物料信息总数
        if($materialCode) {
            $erpMaterial->where('code', 'like', "%$materialCode%");
        }
        if($keyword) {
            $erpMaterial->where('name', 'like', "%$keyword%");
        }
        $count = $erpMaterial->alias('em')
            ->join('oa_erp_inventory ei', 'ei.material_id = em.id')
            ->count();
        // 获取物料信息列表
        if($materialCode) {
            $erpMaterial->where('code', 'like', "%$materialCode%");
        }
        if($keyword) {
            $erpMaterial->where('name', 'like', "%$keyword%");
        }
        $erpMaterialList = $erpMaterial->alias('em')
            ->join('oa_erp_inventory ei', 'ei.material_id = em.id')
            ->order('id desc')
            ->page("$page, $limit")
            ->field('ei.id,em.code,em.name,ei.batch_number,ei.inventory,ei.create_time')
            ->select();

        return Result::returnResult(Result::SUCCESS, $erpMaterialList, $count);
    }

    /**
     * 增加ERP物料
     */
    public function batchSave() {
        return Db::transaction(function () {
            $materialCodes = input('post.materialCodes');
            $materialNames = input('post.materialNames');
            $codeRows = explode("\n", $materialCodes);
            $nameRows = explode("\n", $materialNames);
            $errorResult = Result::returnResult(Result::ERROR);
//            return [
//                '1' => $codeRows,
//                '2' => $nameRows
//            ];
            // 缺少必要参数
            if(!$materialCodes || !$materialNames) {
                return Result::returnResult(Result::ERROR);
            }
            // 行数不一致
            $count = count($codeRows);
            if(count($nameRows) != $count) {
                $errorResult['msg'] = '物料编码和物料名称输入框行数不一致';
                return $errorResult;
            }
            // 判断是否存在空值以及料编码是否存在
            for ($i = 0; $i < $count; $i ++) {
                $codeRows[$i] = trim($codeRows[$i]);
                if(!$codeRows[$i] || !$nameRows[$i]) {
                    $errorResult['msg'] = '第' . ($i + 1) . '行存在空值';
                    return $errorResult;
                }
                $erpMaterial = ErpMaterial::get(['code' => $codeRows[$i]]);
                if($erpMaterial) {
                    $errorResult['msg'] = '第' . ($i + 1) . '行物料编码已存在';
                    return $errorResult;
                }
            }
            // 增加物料信息
            for ($i = 0; $i < $count; $i ++) {
                $erpMaterial = new ErpMaterial();
                $erpMaterial->data([
                    'code' => $codeRows[$i],
                    'name' => trim($nameRows[$i]),
                    'create_time' => date('y-m-d H:i:s',time())
                ]);
                $erpMaterial->save();
            }

            return Result::returnResult(Result::SUCCESS);
        });
    }

    /**
     * 更新ERP物料信息
     */
    public function batchUpdate() {
        return Db::transaction(function () {
            $materialCodes = input('post.materialCodes');
            $batchNumbers = input('post.batchNumbers');
            $inventorys = input('post.materialInventorys');
            $codeRows = explode("\n", $materialCodes);
            $numberRows = explode("\n", $batchNumbers);
            $inventoryRows = explode("\n", $inventorys);
            $errorResult = Result::returnResult(Result::ERROR);
            // 缺少必要参数
            if(!$materialCodes || !$batchNumbers || !$inventorys) {
                return Result::returnResult(Result::ERROR);
            }
            // 如果行数不一致
            $count = count($codeRows);
            if(count($inventoryRows) != $count || count($numberRows) != $count) {
                $errorResult['msg'] = '输入框的行数不一致';
                return $errorResult;
            }
            // 判断是否存在空值以及料编码是否存在等
            for ($i = 0; $i < $count; $i ++) {
                $codeRows[$i] = trim($codeRows[$i]);
                $numberRows[$i] = trim($numberRows[$i]);
                $inventoryRows[$i] = trim($inventoryRows[$i]);
                if(!$codeRows[$i] || !$numberRows[$i] || !$inventoryRows[$i]) {
                    $errorResult['msg'] = '第' . ($i + 1) . '行存在空值';
                    return $errorResult;
                }
                $erpMaterial = ErpMaterial::get(['code' => $codeRows[$i]]);
                if(!$erpMaterial) {
                    $errorResult['msg'] = '第' . ($i + 1) . '行物料编码不存在';
                    return $errorResult;
                }
                if($numberRows[$i] < 0) {
                    $errorResult['msg'] = '第' . ($i + 1) . '行批号不能小于0';
                    return $errorResult;
                }
                if($inventoryRows[$i] < 0) {
                    $errorResult['msg'] = '第' . ($i + 1) . '行物料库存量不能小于0';
                    return $errorResult;
                }
            }
            // 更新物料信息
            for ($i = 0; $i < $count; $i ++) {
                $erpMaterial = ErpMaterial::get(['code' => $codeRows[$i]]);
                $erpInventory = ErpInventory::get(['material_id' => $erpMaterial->id, 'batch_number' => $numberRows[$i]]);
                if(!$erpInventory) {
                    $erpInventory = new ErpInventory();
                    $erpInventory->material_id = $erpMaterial->id;
                    $erpInventory->batch_number = $numberRows[$i];
                }
                $erpInventory->inventory = $inventoryRows[$i];
                $erpInventory->save();
            }

            return Result::returnResult(Result::SUCCESS);
        });
    }

    /**
     * 生成领料信息
     */
    public function createApplyInfo() {
        $materialCodes = input('post.materialCodes');
        $applyAmounts = input('post.applyAmounts');
        $codeRows = explode("\n", $materialCodes);
        $amountRows = explode("\n", $applyAmounts);
        $errorResult = Result::returnResult(Result::ERROR);
        // 缺少必要参数
        if(!$materialCodes || !$applyAmounts) {
            return Result::returnResult(Result::ERROR);
        }
        // 如果行数不一致
        $count = count($codeRows);
        if(count($amountRows) != $count) {
            $errorResult['msg'] = '输入框的行数不一致';
            return $errorResult;
        }
        // 判断是否存在空值以及料编码是否存在等
        for ($i = 0; $i < $count; $i ++) {
            if(!$codeRows[$i] || !$amountRows[$i]) {
                $errorResult['msg'] = '第' . ($i + 1) . '行存在空值';
                return $errorResult;
            }
            $erpMaterial = ErpMaterial::get(['code' => $codeRows[$i]]);
            // 获取总库存量
            $erpInventory = new ErpInventory();
            $totalInventory = $erpInventory->where('material_id', $erpMaterial->id)->sum('inventory');
            if(!$erpMaterial) {
                $errorResult['msg'] = '第' . ($i + 1) . '行物料编码不存在';
                return $errorResult;
            }
            if($amountRows[$i] <= 0) {
                $errorResult['msg'] = '第' . ($i + 1) . '行领料量不能小于1';
                return $errorResult;
            }
            if($amountRows[$i] > $totalInventory) {
                $errorResult['msg'] = '第' . ($i + 1) . '行领料量大于库存量，库存量：' . $totalInventory;
                return $errorResult;
            }
        }
        // 生成领料信息
        $applyInfo = "物料编码 物料名称 领用量 领用前库存量 领用后库存量\n";
        for ($i = 0; $i < $count; $i ++) {
            $erpMaterial = ErpMaterial::get(['code' => $codeRows[$i]]);
            // 获取总库存量
            $erpInventory = new ErpInventory();
            $totalInventory = $erpInventory->where('material_id', $erpMaterial->id)->sum('inventory');
            $afterApplyAmount = $totalInventory - $amountRows[$i];
            $applyInfo .= "$erpMaterial->code $erpMaterial->name $amountRows[$i] $totalInventory $afterApplyAmount\n";
        }
        return Result::returnResult(Result::SUCCESS, $applyInfo);
    }
}