<?php
/**
 * Created by PhpStorm.
 * User: TZX
 * Date: 2020/11/13
 * Time: 15:50
 */

namespace app\index\service;


use app\index\common\DataEnum;
use app\index\model\Mission;
use app\index\model\TableField;
use app\index\model\TableFieldUser;
use app\index\model\TableItem;
use app\index\model\TableItemLabel;
use app\index\model\TableItemProcess;
use app\index\model\TableUser;
use app\index\model\TableWork;
use app\index\model\User;
use think\Collection;
use think\Session;

class TableWorkService
{
    // 获取可见的工作表列表
    public static function getTableList($sessionUserId) {
        $tableWork = new TableWork();
        $tableList = $tableWork->where('status', 1)->alias('tw')->join('oa_table_user tu', "tu.table_id = tw.table_id and tu.user_id = $sessionUserId")->field('tw.table_id,table_name')->select();
        foreach ($tableList as $table) {
            $table->partFields;
        }

        return $tableList;
    }

    // 获取工作表条目标签列表
    public static function getItemLabelList($itemId) {
        $tableItemLabel = new TableItemLabel();
        $labelList = $tableItemLabel->where('item_id', $itemId)->alias('til')->join('oa_label l', 'til.label_id = l.label_id')->column('label_name');

        return $labelList;
    }

    // 获取条目全部字段
    public static function getItemFieldList($tableItem) {
        $itemId = $tableItem->item_id;
        $tableField = new TableField();
        $tableFieldList = $tableField->alias('tf')->where('table_id', $tableItem->table_id)
            ->where('status', 1)
            ->order('sort,field_id')
            ->alias('tf')
            ->join('oa_table_field_value tfv', "tfv.field_id = tf.field_id and tfv.item_id = $itemId", 'LEFT')
            ->field('tf.field_id,name,type,value,field_value')
            ->select();

        foreach ($tableFieldList as $tableField) {
            $tableField->field_value = $tableField->field_value? $tableField->field_value : '';         // 添加新字段而没有对应值的情况
            if($tableField->type == 'user') {            // 单选用户
                if(!$tableField->field_value) {
                    $tableField->field_value = 0;
                } else {
                    $tableField->field_value2 = UserService::userIdToName($tableField->field_value, 1);
                }
            } elseif($tableField->type == 'users') {            // 多选用户
                // 获取多选用户列表
                $tableFieldUser = new TableFieldUser();
                $userList = $tableFieldUser->where('field_id', $tableField->field_id)->where('item_id', $tableItem->item_id)->alias('tfu')->join('oa_user u', 'u.user_id = tfu.user_id')->field('tfu.user_id,user_name')->select();
                $tableField->users = $userList;
            }
        }

        return $tableFieldList;
    }

    // 获取条目部分字段
    public static function getShowItemFieldList($tableItem) {
        $itemId = $tableItem->item_id;
        $tableField = new TableField();
        $tableFieldList = $tableField->alias('tf')->where('table_id', $tableItem->table_id)
            ->where('status', 1)
            ->where('show', 1)
            ->order('sort,field_id')
            ->alias('tf')
            ->join('oa_table_field_value tfv', "tfv.field_id = tf.field_id and tfv.item_id = $itemId", 'LEFT')
            ->field('tf.field_id,name,type,value,field_value')
            ->select();

        foreach ($tableFieldList as $tableField) {
            if($tableField->type == 'user') {
                if($tableField->field_value == null) {
                    $tableField->field_value = '';
                } else {
                    $tableField->field_value = UserService::userIdToName($tableField->field_value, 1);
                }
            } else if($tableField->type == 'users') {
                $fieldValue = [];
                $tableFieldUser = new TableFieldUser();
                $userList = $tableFieldUser->where('field_id', $tableField->field_id)->where('item_id', $tableItem->item_id)->alias('tfu')->join('oa_user u', 'u.user_id = tfu.user_id')->field('tfu.user_id,user_name')->select();
                foreach ($userList as $user) {
                    array_push($fieldValue, $user->user_name);
                }
                $tableField->field_value = implode('；', $fieldValue);
            } else {
                if($tableField->field_value == null) {
                    $tableField->field_value = '';
                }
            }
        }

        return $tableFieldList;
    }

    // 获取条目任务列表
    public static function getMissionList($itemId) {
        $mission = new Mission();
        $missionList = $mission->where('item_id', $itemId)->field('mission_id,mission_title,assignee_id,status,finish_date')->select();
        foreach ($missionList as $mission) {
            $mission->assignee_name = $mission->assignee->user_name;
            $mission->current_process = $mission->process? $mission->process[0]->process_note : '';
            $mission->status = DataEnum::$missionStatus[$mission->status];
            unset($mission->assignee_id, $mission->assignee, $mission->process);
        }
        return $missionList;
    }

    // 判断用户是否有权限查看条目
    public static function isViewTavle($tableId) {
        $sessionUserId = Session::get("info")["user_id"];
        $tableUser = TableUser::get(['table_id' => $tableId, 'user_id' => $sessionUserId]);
        if(!$tableUser) {           // 是否是表的可见人
            if(!UserService::isAdmin($sessionUserId)) {         // 是否是管理员
                return false;
            }
        }

        return true;
    }

    // 获取条目最近处理信息
    public static function getCurrentProcess($itemId) {
        $tableItemProcess = new TableItemProcess();
        $currentProcess = $tableItemProcess->where('item_id', $itemId)->order('process_id desc')->find();

        return $currentProcess? $currentProcess->process_note : '';
    }
}