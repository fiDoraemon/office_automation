<?php

namespace app\index\controller;

use app\common\Result;
use app\index\common\DataEnum;
use app\index\model\Attachment;
use app\index\model\Label;
use app\index\model\Mission;
use app\index\model\TableField;
use app\index\model\TableFieldValue;
use app\index\model\TableItem;
use app\index\model\TableFieldUser;
use app\index\model\TableItemLabel;
use app\index\model\TableItemProcess;
use app\index\model\TableWork;
use app\index\service\LabelService;
use app\index\service\MissionService;
use app\index\service\TableWorkService;
use app\index\service\UserService;
use think\Controller;
use think\Db;
use think\Request;
use think\Session;

class TableItemC extends Controller
{
    /**
     * 显示工作表条目列表
     *
     * @return \think\Response
     */
    public function index($tableId = '', $page = 1, $limit = 10, $keyword = '')
    {
        $sessionUserId = Session::get("info")["user_id"];
        $fields = input('get.');
        $tableItem = new TableItem();
        $tableItem->alias('ti');
        $keyword = input('get.keyword');            // 标题关键词
        $label = input('get.label');            // 标签
        // 获取筛选字段
        $fieldList = [];
        foreach ($fields as $key => $field) {
            if(substr($key,0, 5) == 'field' && $field != '') {
                $fieldId = substr($key, 5);
                $fieldList[$fieldId] = $field;
            }
        }

        if(!$tableId) {
            return Result::returnResult(Result::SUCCESS, [], 0);
        }
        // 获取条目数目
        $tableItem->alias('ti');
        if($keyword) {
            $tableItem->where('item_title', 'like', "%$keyword%");
        }
        if($label) {
            $tableItem->join('oa_table_item_label til','til.item_id = ti.item_id')
                ->join('oa_label l', "l.label_id = til.label_id and l.label_name like '%$label%'");
        }
        if($fieldList) {
            foreach ($fieldList as $key => $value) {
                $tableField = TableField::get($key);
                $tableName = 't' . $key;
                if($tableField->type == 'checkbox') {
                    $tableItem->join("oa_table_field_value $tableName", "$tableName.item_id = ti.item_id and $tableName.field_id = $key and $tableName.field_value like '%$value%'");
                } else if($tableField->type == 'users') {
                    $tableItem->join("oa_table_field_user $tableName", "$tableName.item_id = ti.item_id and $tableName.field_id = $key and $tableName.user_id = '$value'");
                } else {
                    $tableItem->join("oa_table_field_value $tableName", "$tableName.item_id = ti.item_id and $tableName.field_id = $key and $tableName.field_value = '$value'");
                }
            }
        }
        $count = $tableItem->where('table_id', $tableId)->group("ti.item_id")->count();
        // 获取条目列表
        $tableItem->alias('ti');
        if($keyword) {
            $tableItem->where('item_title', 'like', "%$keyword%");
        }
        if($label) {
            $tableItem->join('oa_table_item_label til','til.item_id = ti.item_id')
                ->join('oa_label l', "l.label_id = til.label_id and l.label_name like '%$label%'");
        }
        if($fieldList) {
            foreach ($fieldList as $key => $value) {
                $tableField = TableField::get($key);
                $tableName = 't' . $key;
                if($tableField->type == 'checkbox') {
                    $tableItem->join("oa_table_field_value $tableName", "$tableName.item_id = ti.item_id and $tableName.field_id = $key and $tableName.field_value like '%$value%'");
                } else if($tableField->type == 'users') {
                    $tableItem->join("oa_table_field_user $tableName", "$tableName.item_id = ti.item_id and $tableName.field_id = $key and $tableName.user_id = '$value'");
                } else {
                    $tableItem->join("oa_table_field_value $tableName", "$tableName.item_id = ti.item_id and $tableName.field_id = $key and $tableName.field_value = '$value'");
                }
            }
        }
        $tableItemList = $tableItem->where('table_id', $tableId)->group("ti.item_id")
            ->order('sort desc')->page("$page, $limit")->select();
        // 处理条目列表
        foreach ($tableItemList as $tableItem) {
            $tableItem->creator = UserService::userIdToName($tableItem->creator_id, 1);
            $tableItem->labelList = implode('；', TableWorkService::getItemLabelList($tableItem->item_id));          // 获取标签列表
            $tableItem->fields = TableWorkService::getShowItemFieldList($tableItem);            // 获取条目字段列表
            $tableItem->current_process = TableWorkService::getCurrentProcess($tableItem->item_id);         // 获取最近处理信息
            unset($tableItem->creator_id, $tableItem->table_id);
        }

        return Result::returnResult(Result::SUCCESS, $tableItemList, $count);
    }

    // 获取工作表列表
    public function getTableList() {
        $sessionUserId = Session::get("info")["user_id"];
        $tableList = TableWorkService::getTableList($sessionUserId);
        foreach ($tableList as $table) {
            // 获取工作表的可见人列表
            $table->viewUserList = TableWorkService::getViewUserList($table->table_id);
        }

        return Result::returnResult(Result::SUCCESS, $tableList);
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create($tableId)
    {
        $sessionUserId = Session::get("info")["user_id"];
        $tableWork = TableWork::get($tableId);
        $labelList = LabelService::getLabelList();
        $data = [
            'tableName' => $tableWork->table_name,
            'fields' => $tableWork->fields,
            'labelList' => $labelList
        ];

        return Result::returnResult(Result::SUCCESS, $data);
    }

    /**
     * 保存新建的条目
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        return Db::transaction(function () {           // 开启事务
            $sessionUserId = Session::get("info")["user_id"];
            $fields = input('post.');
            $maxSort = TableWorkService::getMaxItemSort($fields['table_id']);
            // 增加条目信息
            $tableItem = new TableItem([
                'table_id' => $fields['table_id'],
                'item_title' => $fields['item_title'],
                'creator_id' => $sessionUserId,
                'sort' => $maxSort + 1,
                'create_time' => date('Y-m-d H:i:s', time())
            ]);
            $tableItem->save();
            // 增加条目字段对应值
            $checkUserList = explode(';', $fields['checkUserList']);            // 多选字段列表
            foreach ($fields as $key => $value) {
                if(substr($key,0, 5) == 'field') {
                    $fieldId = substr($key,5);
                    if(in_array($key, $checkUserList)) {
                        $userList = explode(';', $fields[$key]);
                        foreach ($userList as $user) {
                            $tableFieldUser = new TableFieldUser();
                            $tableFieldUser->field_id = $fieldId;
                            $tableFieldUser->user_id = $user;
                            $tableFieldUser->item_id = $tableItem->item_id;
                            $tableFieldUser->save();
                        }
                    } else {
                        if(is_array($value)) {          // 如果是自定义多选
                            $array = [];
                            foreach ($value as $one) {
                                array_push($array, $one);
                            }
                            $value = implode(';', $array);
                        }
                        $tableFiledValue = new TableFieldValue();
                        $tableFiledValue->item_id = $tableItem->item_id;
                        $tableFiledValue->field_id = $fieldId;
                        $tableFiledValue->field_value = $value;
                        $tableFiledValue->save();
                    }
                }
            }
            // 处理条目标签
            if($fields['label_list']) {
                $labelList = explode('；', input('label_list'));
                foreach ($labelList as $label) {
                    $labelModel = Label::get(['label_name' => $label]);
                    if(!$labelModel) {
                        $labelModel = new Label();
                        $labelModel->label_name = $label;
                        $labelModel->save();
                    }
                    $tableItemLabel = new TableItemLabel();
                    $tableItemLabel->item_id = $tableItem->item_id;
                    $tableItemLabel->label_id = $labelModel->label_id;
                    $tableItemLabel->save();
                }
            }

            return Result::returnResult(Result::SUCCESS);
        });
    }

    /**
     * 显示指定的条目
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        $tableItem = TableItem::get($id);
        // 判断用户是否是表的可见人
        if(!TableWorkService::isViewTavle($tableItem->table_id)) {
            return Result::returnResult(Result::NO_ACCESS);
        }
        $tableItem->creator_name = UserService::userIdToName($tableItem->creator_id, 1);          // 关联发起人
        $tableItem->table_name = $tableItem->table->table_name;         // 关联工作表
        $tableItem->fields = TableWorkService::getItemFieldList($tableItem);            // 获取工作表字段
        $tableItem->label_list = implode('；', TableWorkService::getItemLabelList($id));         // 获取条目标签列表
        $labelList = LabelService::getLabelList();          // 获取标签列表
        // 获取任务列表
        $tableItem->missionList = TableWorkService::getMissionList($tableItem->item_id);
        // 获取条目处理列表
        foreach ($tableItem->processList as $process) {
            // 获取条目附件列表
            foreach ($process->attachments as $attachment) {
                $attachment->save_path = DataEnum::uploadDir . $attachment->save_path;
            }
        }
        unset($tableItem->item_id, $tableItem->creator_id, $tableItem->table_id, $tableItem->table);
        $data = [
            'itemDetail' => $tableItem,
            'labelList' => $labelList
        ];

        return Result::returnResult(Result::SUCCESS, $data);
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        return Db::transaction(function () use($id) {           // 开启事务
            $sessionUserId = Session::get("info")["user_id"];
            $tableItem = TableItem::get($id);
            $fields = input('put.');
            // 更新条目信息
            $tableItem->item_title = $fields['item_title'];
            $tableItem->save();
            // 更新条目字段信息
            $checkUserList = explode(';', $fields['checkUserList']);            // 多选字段列表
            foreach ($fields as $key => $value) {
                if(substr($key,0, 5) == 'field') {
                    $fieldId = substr($key,5);
                    if(in_array($key, $checkUserList)) {
                        $submitUserList = explode(';', $fields[$key]);            // 提交的多选用户列表
                        // 获取当前的多选用户列表
                        $tableFieldUser = new TableFieldUser();
                        $currentUserList = $tableFieldUser->where('item_id', $tableItem->item_id)->where('field_id', $fieldId)->column('user_id');
                        $newUserList = array_diff($submitUserList, $currentUserList);         // 新增加的用户
                        $cancelUserList = array_diff($currentUserList, $submitUserList);        // 取消的用户
                        foreach ($newUserList as $newUser) {
                            $tableFieldUser = new TableFieldUser();
                            $tableFieldUser->field_id = $fieldId;
                            $tableFieldUser->user_id = $newUser;
                            $tableFieldUser->item_id = $tableItem->item_id;
                            $tableFieldUser->save();
                        }
                        foreach ($cancelUserList as $cancelUser) {
                            $tableFieldUser = TableFieldUser::get(['field_id' => $fieldId, 'user_id' => $cancelUser, 'item_id' => $tableItem->item_id]);
                            if($tableFieldUser) {
                                $tableFieldUser->delete();
                            }
                        }
                    } else {
                        if(is_array($value)) {          // 如果是自定义多选
                            $array = [];
                            foreach ($value as $one) {
                                array_push($array, $one);
                            }
                            $value = implode(';', $array);
                        }
                        $tableFiledValue = TableFieldValue::get(['item_id' => $tableItem->item_id, 'field_id' => $fieldId]);
                        if(!$tableFiledValue) {
                            $tableFiledValue = new TableFieldValue();
                            $tableFiledValue->item_id = $tableItem->item_id;
                            $tableFiledValue->field_id = $fieldId;
                        }
                        $tableFiledValue->field_value = $value;
                        $tableFiledValue->save();
                    }
                }
            }
            // 处理条目标签
            if($fields['label_list']) {
                $labelList = explode('；', $fields['label_list']);
                foreach ($labelList as $label) {
                    $labelModel = Label::get(['label_name' => $label]);
                    if(!$labelModel) {
                        $labelModel = new Label();
                        $labelModel->label_name = $label;
                        $labelModel->save();
                    }
                    $tableItemLabel = TableItemLabel::get(['item_id' => $tableItem->item_id, 'label_id' => $labelModel->label_id]);
                    if(!$tableItemLabel) {
                        $tableItemLabel = new TableItemLabel();
                        $tableItemLabel->item_id = $tableItem->item_id;
                        $tableItemLabel->label_id = $labelModel->label_id;
                        $tableItemLabel->save();
                    }
                }
            }
            // 增加条目处理信息
            if($fields['process_note'] || $fields['attachment_list']) {
                $tableItemProcess = new TableItemProcess();
                $tableItemProcess->item_id = $id;
                $tableItemProcess->handler_id = $sessionUserId;
                $tableItemProcess->process_note = $fields['process_note'];
                $tableItemProcess->save();
            }
            // 处理条目附件
            if($fields['attachment_list']) {
                $attachmentIdList = explode(';', $fields['attachment_list']);
                foreach ($attachmentIdList as $attachmentId) {
                    $attachment = Attachment::get($attachmentId);
                    $attachment->attachment_type = 'item';
                    $attachment->related_id = $tableItemProcess->process_id;
                    $attachment->save();
                }
            }
            // 添加条目任务
            if(isset($fields['assignee'])) {
                foreach ($fields['assignee'] as $key => $assignee) {
                    $mission = new Mission();
                    $mission->data([
                        'mission_title' => $fields['mission_title'][$key],
                        'reporter_id' => $sessionUserId,
                        'assignee_id' => $assignee,
                        'finish_date' => $fields['finish_date'][$key],
                        'description' => $fields['description'][$key],
                        'item_id' => $tableItem->item_id,
                        'create_time' => date('Y-m-d H:i:s', time())
                    ]);
                    $mission->save();
                    MissionService::sendMessge($mission->mission_id);           // 发送钉钉消息
                }
            }

            return Result::returnResult(Result::SUCCESS);
        });
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //
    }

    // 判断条目是否存在
    public function isExist($tableId, $sort)
    {
        $tableItem = TableItem::get(['table_id' => $tableId, 'sort' => $sort]);
        if($tableItem) {
            return Result::returnResult(Result::SUCCESS, $tableItem->item_id);
        } else {
            return Result::returnResult(Result::OBJECT_NOT_EXIST);
        }
    }
}
