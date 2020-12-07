<?php
/**
 * Created by PhpStorm.
 * User: TZX
 * Date: 2020/11/13
 * Time: 15:50
 */

namespace app\index\service;


use app\index\common\DataEnum;
use app\index\model\Label;
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
    /**
     * 获取可见的工作表列表
     * @param $sessionUserId
     * @return false|\PDOStatement|string|Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getTableList($sessionUserId) {
        $tableWork = new TableWork();
        $tableList = $tableWork->where('status', 1)->alias('tw')->join('oa_table_user tu', "tu.table_id = tw.table_id and tu.user_id = $sessionUserId")->field('tw.table_id,table_name')->select();
        foreach ($tableList as $table) {
            $table->partFields;
        }

        return $tableList;
    }

    /**
     * 获取工作表条目标签列表
     * @param $itemId
     * @return array
     */
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
            // 添加新字段而没有对应值的情况
            if($tableField->field_value == null) {
                $tableField->field_value = '';
            }
            if($tableField->type == 'user') {            // 单选用户
                if($tableField->field_value) {
                    $tableField->field_value2 = UserService::userIdToName($tableField->field_value, 1);
                } else {
                    $tableField->field_value = 0;
                }
            } else if($tableField->type == 'users') {            // 多选用户
                // 获取多选用户列表
                $tableFieldUser = new TableFieldUser();
                $userList = $tableFieldUser->where('field_id', $tableField->field_id)
                    ->where('item_id', $tableItem->item_id)
                    ->alias('tfu')->join('oa_user u', 'u.user_id = tfu.user_id')
                    ->field('tfu.user_id,user_name')->select();
                $tableField->users = $userList;
            } else if($tableField->type == 'mission') {            // 任务
                $missions = str_ireplace('；',',', $tableField->field_value);
                $mission = new Mission();
                $missionList = $mission->where('mission_id', 'in', $missions)->field('mission_id,mission_title')->select();
                $tableField->missionList = $missionList;
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
            // 添加新字段而没有对应值的情况
            if($tableField->field_value == null) {
                $tableField->field_value = '';
            }
            if($tableField->type == 'user') {
                if($tableField->field_value) {
                    $tableField->field_value = UserService::userIdToName($tableField->field_value, 1);
                } else {
                    $tableField->field_value = '';
                }
            } else if($tableField->type == 'users') {
                $fieldValue = [];
                $tableFieldUser = new TableFieldUser();
                $userList = $tableFieldUser->where('field_id', $tableField->field_id)->where('item_id', $tableItem->item_id)->alias('tfu')->join('oa_user u', 'u.user_id = tfu.user_id')->field('tfu.user_id,user_name')->select();
                foreach ($userList as $user) {
                    array_push($fieldValue, $user->user_name);
                }
                $tableField->field_value = implode('；', $fieldValue);
            } else if($tableField->type == 'checkbox') {
                $tableField->field_value = str_ireplace(";","；", $tableField->field_value);
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

    /*
     * 判断用户是否有权限查看条目
     */
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

    /**
     * 获取条目最近处理信息
     * @param $itemId
     * @return mixed|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getCurrentProcess($itemId) {
        $tableItemProcess = new TableItemProcess();
        $currentProcess = $tableItemProcess->where('item_id', $itemId)->order('process_id desc')->find();

        return $currentProcess? $currentProcess->process_note : '';
    }

    /**
     * 获取工作表最大条目序号
     * @param $tableId
     * @return mixed
     */
    public static function getMaxItemSort($tableId) {
        $tableItem = new TableItem();
        $maxItemSort = $tableItem->where('table_id', $tableId)->max('sort');

        return $maxItemSort;
    }

    /**
     * 获取工作表的可见人列表
     * @param $tableId
     * @return false|\PDOStatement|string|Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function getViewUserList($tableId) {
        $tableUser = new TableUser();
        $viewUserList = $tableUser->alias('tu')->where('table_id', $tableId)->join('oa_user u', 'u.user_id = tu.user_id')->field('tu.user_id,u.user_name')->select();
        return $viewUserList;
    }

    /**
     * 处理条目标签
     * @param $labelList
     * @param $itemId
     * @return bool
     * @throws \think\exception\DbException
     */
    public static function processItemlabel($labelList, $itemId) {
        $labelList = explode('；', $labelList);
        $itemLabels = [];          // 目前的条目标签号列表
        // 新增加的
        foreach ($labelList as $label) {
            $labelModel = Label::get(['label_name' => $label]);
            if(!$labelModel) {
                $labelModel = new Label();
                $labelModel->label_name = $label;
                $labelModel->save();
            }
            $tableItemLabel = TableItemLabel::get(['item_id' => $itemId, 'label_id' => $labelModel->label_id]);
            if(!$tableItemLabel) {
                $tableItemLabel = new TableItemLabel();
                $tableItemLabel->item_id = $itemId;
                $tableItemLabel->label_id = $labelModel->label_id;
                $tableItemLabel->save();
            }
            array_push($itemLabels, $tableItemLabel->label_id);
        }
        // 删除的
        $tableItemLabel = new TableItemLabel();
        $tableItemLabel->where('item_id', $itemId)->where('label_id', 'not in', $itemLabels)->delete();

        return true;
    }

    /**
     * 处理条目多选用户
     * @param $users 以；分隔的名字字符串
     * @param $itemId
     * @param $fieldId
     * @return bool
     * @throws \think\exception\DbException
     */
    public static function processItemUsers($users, $itemId, $fieldId) {
        $userList = explode('；', $users);            // 多选用户列表
        $fiedlsUsers = [];          // 当前的条目多选用户号列表
        // 新增加的
        foreach ($userList as $user) {
            if($user == '') {
                continue;
            }
            $userModel = User::get(['user_name' => $user]);
            $tableFieldUser = TableFieldUser::get(['item_id' => $itemId, 'field_id' => $fieldId, 'user_id' => $userModel->user_id]);
            if(!$tableFieldUser) {
                $tableFieldUser = new TableFieldUser();
                $tableFieldUser->item_id = $itemId;
                $tableFieldUser->field_id = $fieldId;
                $tableFieldUser->user_id = $userModel->user_id;
                $tableFieldUser->save();
            }
            array_push($fiedlsUsers, $userModel->user_id);
        }
        // 删除的
        $tableFieldUser = new TableFieldUser();
        $tableFieldUser->where('item_id', $itemId)->where('field_id', $fieldId)
            ->where('user_id', 'not in', $fiedlsUsers)->delete();

        return true;
    }
}