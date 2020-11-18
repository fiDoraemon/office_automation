<?php

namespace app\index\controller;

use app\common\Result;
use app\index\model\Attachment;
use app\index\model\Label;
use app\index\model\TableField;
use app\index\model\TableFieldValue;
use app\index\model\TableItem;
use app\index\model\TableFieldUser;
use app\index\model\TableItemLabel;
use app\index\model\TableItemProcess;
use app\index\model\TableWork;
use app\index\service\LabelService;
use app\index\service\TableWorkService;
use app\index\service\UserService;
use think\Controller;
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
        if(!$tableId) {
            return Result::returnResult(Result::SUCCESS, [], 0);
        }
        $tableItem = new TableItem();
        $keyword = input('get.keyword');
        $label = input('get.label');
        if($keyword) {
            $tableItem->where('item_title', 'like', "%$keyword%");
        }
        if($label) {
            $tableItem->alias('ti')->join('oa_table_item_label til','til.item_id = ti.item_id')->limit(1)->join('oa_label l', "l.label_id = til.label_id and l.label_name like '%$label%'");
        }
        $count = $tableItem->where('table_id', $tableId)->count();
        if($keyword) {
            $tableItem->where('item_title', 'like', "%$keyword%");
        }
        if($label) {
            $tableItem->alias('ti')->join('oa_table_item_label til', 'ti.item_id = til.item_id')->limit(1)->join('oa_label l', "l.label_id = til.label_id and l.label_name like '%$label%'");
        }
        $tableItemList = $tableItem->where('table_id', $tableId)->page("$page, $limit")->select();

        foreach ($tableItemList as $tableItem) {
            // 获取标签列表
            $tableItem->labelList = implode('；', TableWorkService::getItemLabelList($tableItem->item_id));
            // 获取条目字段列表
            foreach ($tableItem->partFields as $field) {
                if($field->type == 'user') {
                    $field->field_value = UserService::userIdToName($field->field_value, 1);
                }
            }
        }

        return Result::returnResult(Result::SUCCESS, $tableItemList, $count);
    }

    // 获取工作表列表
    public function getTableList() {
        $tableList = TableWorkService::getTableList();

        return Result::returnResult(Result::SUCCESS, $tableList);
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        $tableList = TableWorkService::getTableList();          // 获取工作表列表
        $labelList = LabelService::getLabelList();
        $data = [
            'tableList' => $tableList,
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
        $sessionUserId = Session::get("info")["user_id"];
        $fields = input('post.');
        // 增加条目信息
        $tableItem = new TableItem([
            'table_id' => $fields['table_id'],
            'item_title' => $fields['item_title'],
            'creator_id' => $sessionUserId,
            'create_time' => date('Y-m-d H:i:s', time())
        ]);
        $tableItem->save();
        // 增加条目字段对应值
        foreach ($fields as $key => $value) {
            if(substr($key,0, 5) == 'field') {
                $checkUserList = explode(';', $fields['checkUserList']);            // 多选字段列表\
                $tableFiledValue = new TableFieldValue();
                $tableFiledValue->item_id = $tableItem->item_id;
                $tableFiledValue->field_id = substr($key,5);
                $tableFiledValue->field_value = $value;
                $tableFiledValue->save();
                if(in_array($key, $checkUserList)) {
                    $userList = explode(';', $fields[$key]);
                    foreach ($userList as $user) {
                        $tableFieldUser = new TableFieldUser();
                        $tableFieldUser->field_id = substr($key,5);
                        $tableFieldUser->user_id = $user;
                        $tableFieldUser->item_id = $tableItem->item_id;
                        $tableFieldUser->save();
                    }
                }
            }
        }
        // 处理条目标签
        if($fields['label_list']) {
            $labelList = explode('；', input('label_list'));
            foreach ($labelList as $label) {
                $label = Label::get(['label_name' => $label]);
                if(!$label) {
                    $label = new Label();
                    $label->label_name = $label;
                    $label->save();
                }
                $tableItemLabel = new TableItemLabel();
                $tableItemLabel->item_id = $tableItem->item_id;
                $tableItemLabel->label_id = $label->label_id;
                $tableItemLabel->save();
            }
        }

        return Result::returnResult(Result::SUCCESS);
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
        $tableItem->creator_name = UserService::userIdToName($tableItem->creator_id, 1);          // 关联发起人
        $tableItem->table_name = $tableItem->table->table_name;         // 关联工作表
        // 获取工作表字段
        foreach ($tableItem->fields as $field) {
            if($field->type == 'user') {
                $field->field_value2 = UserService::userIdToName($field->field_value, 1);
            } else if($field->type == 'users') {
                // 获取多选用户列表
                $tableFieldUser = new TableFieldUser();
                $userList = $tableFieldUser->where('field_id', $field->field_id)->where('item_id', $tableItem->item_id)->alias('tfu')->join('oa_user u', 'u.user_id = tfu.user_id')->field('tfu.user_id,user_name')->select();
                $field->users = $userList;
            }
        }
        $tableItem->label_list = implode('；', TableWorkService::getItemLabelList($id));         // 获取条目标签列表
        // 获取条目处理列表
        foreach ($tableItem->processList as $process) {
            $process->attachments;            // 获取条目附件列表
        }
        unset($tableItem->item_id, $tableItem->creator_id, $tableItem->table_id, $tableItem->table);
        $labelList = LabelService::getLabelList();          // 获取标签列表
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
        $sessionUserId = Session::get("info")["user_id"];
        $tableItem = TableItem::get($id);
        $fields = input('put.');
        // 更新条目信息
        $tableItem->item_title = $fields['item_title'];
        $tableItem->save();
        // 更新条目字段信息
        foreach ($fields as $key => $value) {
            if(substr($key,0, 5) == 'field') {
                $checkUserList = explode(';', $fields['checkUserList']);            // 多选字段列表
                if(!in_array($key, $checkUserList)) {
                    $tableFiledValue = TableFieldValue::get(['item_id' => $tableItem->item_id, 'field_id' => substr($key,5)]);
                    $tableFiledValue->field_value = $value;
                    $tableFiledValue->save();
                } else {
                    $submitUserList = explode(';', $fields[$key]);            // 提交的多选用户列表
                    // 获取当前的多选用户列表
                    $tableFieldUser = new TableFieldUser();
                    $currentUserList = $tableFieldUser->where('item_id', $tableItem->item_id)->where('field_id', substr($key,5))->column('user_id');
                    $newUserList = array_diff($submitUserList, $currentUserList);         // 新增加的用户
                    $cancelUserList = array_diff($currentUserList, $submitUserList);        // 取消的用户
                    foreach ($newUserList as $newUser) {
                        $tableFieldUser = new TableFieldUser();
                        $tableFieldUser->field_id = substr($key,5);
                        $tableFieldUser->user_id = $newUser;
                        $tableFieldUser->item_id = $tableItem->item_id;
                        $tableFieldUser->save();
                    }
                    foreach ($cancelUserList as $cancelUser) {
                        $tableFieldUser = TableFieldUser::get(['field_id' => substr($key,5), 'user_id' => $cancelUser, 'item_id' => $tableItem->item_id]);
                        if($tableFieldUser) {
                            $tableFieldUser->delete();
                        }
                    }
                }
            }
        }
        // 处理条目标签
        if($fields['label_list']) {
            $labelList = explode('；', $fields['label_list']);
            foreach ($labelList as $label) {
                $label = Label::get(['label_name' => $label]);
                if(!$label) {
                    $label = new Label();
                    $label->label_name = $label;
                    $label->save();
                }
                $tableItemLabel = TableItemLabel::get(['item_id' => $tableItem->item_id, 'label_id' => $label->label_id]);
                if(!$tableItemLabel) {
                    $tableItemLabel = new TableItemLabel();
                    $tableItemLabel->item_id = $tableItem->item_id;
                    $tableItemLabel->label_id = $label->label_id;
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

        return Result::returnResult(Result::SUCCESS, $tableItem);
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
}
