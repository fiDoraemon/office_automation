<?php

namespace app\index\controller;

use app\common\Result;
use app\index\model\Label;
use app\index\model\TableField;
use app\index\model\TableFieldValue;
use app\index\model\TableItem;
use app\index\model\TableFieldUser;
use app\index\model\TableItemLabel;
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
        foreach ($fields as $key => $field) {
            if(substr($key,0, 5) == 'field') {
                $tableFiledValue = new TableFieldValue();
                $tableFiledValue->item_id = $tableItem->item_id;
                $tableFiledValue->field_id = substr($key,5);
                $tableFiledValue->value = $field;
                $tableFiledValue->save();
            }
        }
        // 处理条目多选用户
        if($fields['checkUserList']) {
            $checkUserList = explode(';', $fields['checkUserList']);
            foreach ($checkUserList as $checkUser) {
                $userList = explode(';', $fields[$checkUser]);
                foreach ($userList as $user) {
                    $tableieldUser = new TableFieldUser();
                    $tableieldUser->field_id = substr($checkUser,5);
                    $tableieldUser->user_id = $user;
                    $tableieldUser->item_id = $tableItem->item_id;
                    $tableieldUser->save();
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
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
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
        //
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
