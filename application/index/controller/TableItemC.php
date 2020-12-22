<?php

namespace app\index\controller;

use app\common\Result;
use app\common\util\ExcelUtil;
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
use app\index\model\User;
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
            ->order('sort desc')->page("$page, $limit")->field('ti.item_id,ti.table_id,ti.item_title,ti.creator_id,ti.sort,ti.color,ti.create_time,ti.update_time')->select();
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
        $data = [
            'tableList' => $tableList,
            'isAdmin' => UserService::isAdmin($sessionUserId)? 1 : 0
        ];

        return Result::returnResult(Result::SUCCESS, $data);
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
                'color' => $fields['color'],
                'create_time' => date('Y-m-d H:i:s', time())
            ]);
            $tableItem->save();
            // 增加条目字段对应值
            $checkUserList = explode(';', $fields['checkUserList']);            // 多选字段列表
            foreach ($fields as $key => $value) {
                if(substr($key,0, 5) == 'field') {
                    $fieldId = substr($key,5);
                    $tableField = TableField::get($fieldId);
                    if($tableField->type == 'users') {
                        $userList = explode(';', $fields[$key]);
                        foreach ($userList as $user) {
                            $tableFieldUser = new TableFieldUser();
                            $tableFieldUser->field_id = $fieldId;
                            $tableFieldUser->user_id = $user;
                            $tableFieldUser->item_id = $tableItem->item_id;
                            $tableFieldUser->save();
                        }
                    } else {
                        // 如果是自定义多选
                        if($tableField->type == 'checkbox') {
                            $value = implode(';', $value);
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
                TableWorkService::processItemlabel($fields['label_list'], $tableItem->item_id);
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
            $tableItem->color = $fields['color'];
            $tableItem->save();
            // 更新条目字段信息
//            $checkUserList = explode(';', $fields['checkUserList']);            // 多选字段列表
            foreach ($fields as $key => $value) {
                if(substr($key,0, 5) == 'field') {
                    $fieldId = substr($key,5);
                    $tableField = TableField::get($fieldId);
                    if($tableField->type == 'users') {
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
                        // 如果是自定义多选
                        if($tableField->type == 'checkbox') {
                            $value = implode(';', $value);
                        }
                        $tableFiledValue = TableFieldValue::get(['item_id' => $tableItem->item_id, 'field_id' => $fieldId]);
                        // 不存在对应的值记录时
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
                TableWorkService::processItemlabel($fields['label_list'], $id);
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

    // 通过导入excel文件更新条目信息
    public function updateItemByImportExcel($tableId) {
        return Db::transaction(function () use($tableId) {           // 开启事务
            $tableWork = TableWork::get($tableId);
            // 获取工作表字段名称列表
            $cols = ['id', '标题', '发起人', '最新处理信息', '标签', '创建时间', '更新时间'];
            foreach ($tableWork->partFields as $field) {
                array_splice($cols, count($cols) - 4, 0, [$field->name]);
            }
            // 获取上传的文件信息
            $file = $this->request->file('file');
            if($file) {
                // 文件移动到temp文件夹
                $info = $file->validate(['size' => 52428800])->rule('uniqid')->move(ROOT_PATH . 'public/upload/temp/');
                $filePath = ROOT_PATH . 'public/upload/temp/' . $info->getFilename();
                $itemList = ExcelUtil::readExcel($filePath, count($cols));
                unset($info);           // 释放文件
                unlink($filePath);          // 删除临时文件
            } else {
                return Result::returnResult(Result::UPLOAD_ERROR);
            }
            // 判断表格信息是否符合要求
            $errorResult = Result::returnResult(Result::ERROR);
            if ($itemList[0] != $cols) {
                $errorResult['msg'] = '表头不正确';
                return $errorResult;
            }
            for ($i = 1; $i < count($itemList); $i++) {
                $item = $itemList[$i];
                $tableItem = TableItem::get(['table_id' => $tableId, 'sort' => $item[0]]);
                if ($tableItem) {
                    if ($tableItem->item_title != $item[1]) {
                        $errorResult['msg'] = '第' . $i . '行条目号和条目标题不对应';
                        return $errorResult;
                    }
                } else {
                    $errorResult['msg'] = '第' . $i . '行条目不存在';
                    return $errorResult;
                }
                for ($j = 3; $j < count($cols) - 4; $j++) {
                    $tableField = $tableWork->partFields[$j - 3];
                    if ($tableField->type == 'user' || $tableField->type == 'users') {
                        $userList = explode('；', $item[$j]);
                        foreach ($userList as $user) {
                            if (!UserService::isRightName($user)) {
                                $errorResult['msg'] = '第' . $i . '行条目' . $tableField->name . '字段值中的用户不存在或存在两个';
                                return $errorResult;
                            }
                        }
                    } else if ($tableField->type == 'select' || $tableField->type == 'checkbox') {
                        $valueList = explode('；', $item[$j]);
                        foreach ($valueList as $value) {
                            if($value == '') {
                                continue;
                            }
                            $selectList = explode('，', $tableField->value);
                            $result = false;
                            foreach ($selectList as $select) {
                                if ($value == $select) {
                                    $result = true;
                                    break;
                                }
                            }
                            if (!$result) {
                                $errorResult['msg'] = '第' . $i . '行条目' . $tableField->name . '字段值不在允许的范围内';
                                return $errorResult;
                            }
                        }
                    } else if($tableField->type == 'mission') {
                        if($item[$j]) {
                            $result = $this->checkMission($item[$j]);
                            if($result['code'] != 0) {
                                $errorResult['msg'] = '第' . $i . '行条目' . $tableField->name . '中的任务不存在';
                                return $errorResult;
                            }
                        }
                    }
                }
            }
            // 更新条目信息
            for ($i = 1; $i < count($itemList); $i++) {
                $item = $itemList[$i];          // 条目信息
                $tableItem = TableItem::get(['table_id' => $tableId, 'sort' => $item[0]]);
                // 处理条目标签
                TableWorkService::processItemlabel($item[count($item) - 3], $tableItem->item_id);
                // 从第四列开始处理工作表字段
                for ($j = 3; $j < count($cols) - 4; $j++) {
                    $tableField = $tableWork->partFields[$j - 3];            // 工作表字段对象
                    // 如果是空值则不更新
                    if(!$item[$j]) {
                        continue;
                    }
                    if($tableField->type == 'users') {
                        TableWorkService::processItemUsers($item[$j], $tableItem->item_id, $tableField->field_id);
                    } else {
                        $tableFieldValue = TableFieldValue::get(['item_id' => $tableItem->item_id, 'field_id' => $tableField->field_id]);
                        if(!$tableFieldValue) {
                            $tableFieldValue = new TableFieldValue();
                            $tableFieldValue->item_id = $tableItem->item_id;
                            $tableFieldValue->field_id = $tableField->field_id;
                        }
                        if($tableField->type == 'user') {
                            if ($item[$j] == '') {
                                $userId = 0;
                            } else {
                                $userId = UserService::userIdToName($item[$j], 2);
                            }
                            $tableFieldValue->field_value = $userId;
                        } else if($tableField->type == 'checkbox') {
                            $tableFieldValue->field_value = str_ireplace('；',';', $item[$j]);
                        } else {
                            $tableFieldValue->field_value = $item[$j];
                        }
                        $tableFieldValue->save();
                    }
                }
            }

            return Result::returnResult(Result::SUCCESS);
        });
    }

    /**
     * 验证任务是否存在
     * @param $missionList
     * @return array
     * @throws \think\exception\DbException
     */
    public function checkMission($missionList) {
        $missionList = explode('；', $missionList);
        foreach ($missionList as $mission) {
            $missionModel = Mission::get($mission);
            if(!$missionModel) {
                return Result::returnResult(Result::OBJECT_NOT_EXIST);
            }
        }

        return Result::returnResult(Result::SUCCESS);
    }

    /**
     * 通过导入excel文件更新条目信息
     * @param $tableId
     * @return mixed
     */
    public function addItemByImportExcel($tableId) {
        return Db::transaction(function () use($tableId) {           // 开启事务
            $tableWork = TableWork::get($tableId);
            // 获取工作表字段名称列表
            $cols = ['id', '标题', '发起人', '最新处理信息', '标签', '创建时间', '更新时间'];
            foreach ($tableWork->partFields as $field) {
                array_splice($cols, count($cols) - 4, 0, [$field->name]);
            }
            // 获取上传文件信息
            $file = $this->request->file('file');
            if($file) {
                // 文件移动到temp文件夹
                $info = $file->validate(['size' => 52428800])->rule('uniqid')->move(ROOT_PATH . 'public/upload/temp/');
                $filePath = ROOT_PATH . 'public/upload/temp/' . $info->getFilename();
                $itemList = ExcelUtil::readExcel($filePath, count($cols));
                unset($info);           // 释放文件
                unlink($filePath);          // 删除临时文件
            } else {
                return Result::returnResult(Result::UPLOAD_ERROR);
            }
            // 判断表格信息是否符合要求
            $errorResult = Result::returnResult(Result::ERROR);
            if ($itemList[0] != $cols) {
                $errorResult['msg'] = '表头不正确';
                return $errorResult;
            }
            for ($i = 1; $i < count($itemList); $i++) {
                $item = $itemList[$i];
                if($item[1] == '') {
                    $errorResult['msg'] = '条目标题不能为空';
                    return $errorResult;
                }
                for ($j = 3; $j < count($cols) - 4; $j++) {
                    $tableField = $tableWork->partFields[$j - 3];
                    if ($tableField->type == 'user' || $tableField->type == 'users') {
                        $userList = explode('；', $item[$j]);
                        foreach ($userList as $user) {
                            if (!UserService::isRightName($user)) {
                                $errorResult['msg'] = '第' . $i . '行条目' . $tableField->name . '字段值中的用户不存在或存在两个';
                                return $errorResult;
                            }
                        }
                    } else if ($tableField->type == 'select' || $tableField->type == 'checkbox') {
                        $valueList = explode('；', $item[$j]);
                        foreach ($valueList as $value) {
                            if($value == '') {
                                continue;
                            }
                            $selectList = explode('，', $tableField->value);
                            $result = false;
                            foreach ($selectList as $select) {
                                if ($value == $select) {
                                    $result = true;
                                    break;
                                }
                            }
                            if (!$result) {
                                $errorResult['msg'] = '第' . $i . '行条目' . $tableField->name . '字段值不在允许的范围内';
                                return $errorResult;
                            }
                        }
                    } else if($tableField->type == 'mission') {
                        if($item[$j]) {
                            $result = $this->checkMission($item[$j]);
                            if($result['code'] != 0) {
                                $errorResult['msg'] = '第' . $i . '行条目' . $tableField->name . '中的任务不存在';
                                return $errorResult;
                            }
                        }
                    }
                }
            }
            // 增加条目信息
            for ($i = 1; $i < count($itemList); $i++) {
                $item = $itemList[$i];          // 条目信息
                // 增加条目
                $sessionUserId = Session::get('info')['user_id'];
                $maxSort = TableWorkService::getMaxItemSort($tableId);
                $tableItem = new TableItem([
                    'table_id' => $tableId,
                    'item_title' => $item[1],
                    'creator_id' => $sessionUserId,
                    'sort' => $maxSort + 1,
                    'create_time' => date('Y-m-d H:i:s', time())
                ]);
                $tableItem->save();
                // 处理条目标签
                TableWorkService::processItemlabel($item[count($item) - 3], $tableItem->item_id);
                // 从第四列开始处理工作表字段
                for ($j = 3; $j < count($cols) - 4; $j++) {
                    $tableField = $tableWork->partFields[$j - 3];            // 工作表字段对象
                    if($tableField->type == 'users') {
                        $userList = explode('；', $item[$j]);
                        foreach ($userList as $user) {
                            if($user == '') {
                                continue;
                            }
                            $userId = UserService::userIdToName($user, 2);
                            $tableFieldUser = new TableFieldUser();
                            $tableFieldUser->item_id = $tableItem->item_id;
                            $tableFieldUser->field_id = $tableField->field_id;
                            $tableFieldUser->user_id = $userId;
                            $tableFieldUser->save();
                        }
                    } else {
                        // 先获取字段值记录（防止重复新增记录）
                        $tableFieldValue = TableFieldValue::get(['item_id' => $tableItem->item_id, 'field_id' => $tableField->field_id]);
                        if(!$tableFieldValue) {
                            $tableFieldValue = new TableFieldValue();
                            $tableFieldValue->item_id = $tableItem->item_id;
                            $tableFieldValue->field_id = $tableField->field_id;
                        }
                        if($tableField->type == 'user') {
                            if ($item[$j] == '') {
                                $userId = 0;
                            } else {
                                $userId = UserService::userIdToName($item[$j], 2);
                            }
                            $tableFieldValue->field_value = $userId;
                        } else if($tableField->type == 'checkbox') {
                            $tableFieldValue->field_value = str_ireplace('；',';', $item[$j]);
                        } else {
                            $tableFieldValue->field_value = $item[$j];
                        }
                        $tableFieldValue->save();
                    }
                }
            }

            return Result::returnResult(Result::SUCCESS);
        });
    }

    /**
     * 添加任务字段预定义任务
     */
    public function addFieldMission()
    {
        return Db::transaction(function () {
            $sessionUserId = Session::get("info")["user_id"];
            $fields = input('post.');
            $tableItem = TableItem::get($fields['itemId']);
            $tableWork = TableWork::get($tableItem->table_id);
            $tableField = TableField::get($fields['fieldId']);
            $today = date('Y-m-d', time());

            foreach ($fields['missionTitles'] as $missionTitle) {
                // 如果任务标题为空或者任务已存在
                if ($missionTitle == '' || Mission::get(['mission_title' => $missionTitle])) {
                    continue;
                }
                $description = '对应' . $tableWork->table_name . '-' . $tableItem->item_title . '条目' . '-' . $tableField->name . '字段';
                $mission = new Mission();
                $mission->data([
                    'mission_title' => $missionTitle,
                    'reporter_id' => $sessionUserId,
                    'assignee_id' => $sessionUserId,
                    'description' => $description,
                    'start_date' => $today,
                    'finish_date' => $today
                ]);
                $mission->save();
                // 发送钉钉消息
                MissionService::sendMessge($mission->mission_id);
                // 任务关联工作表条目字段
                $tableFieldValue = TableFieldValue::get(['item_id' => $fields['itemId'], 'field_id' => $fields['fieldId']]);
                if (!$tableFieldValue) {
                    $tableFieldValue = new TableFieldValue();
                    $tableFieldValue->item_id = $fields['itemId'];
                    $tableFieldValue->field_id = $fields['fieldId'];
                    $tableFieldValue->field_value = '';         // TODO 按道理不需要这步
                    $tableFieldValue->save();
                }
                if ($tableFieldValue->field_value) {
                    $tableFieldValue->field_value = $tableFieldValue->field_value . '；' . $mission->mission_id;
                } else {
                    $tableFieldValue->field_value = $mission->mission_id;
                }
                $tableFieldValue->save();
            }

            return Result::returnResult(Result::SUCCESS);
        });
    }
}
