<?php
/**
 * Created by PhpStorm.
 * User: TZX
 * Date: 2020/11/13
 * Time: 15:50
 */

namespace app\index\service;


use app\index\model\TableItemLabel;
use app\index\model\TableWork;

class TableWorkService
{
    // 获取工作表列表
    public static function getTableList() {
        $tableWork = new TableWork();
        $tableList = $tableWork->field('table_id,table_name')->where('status', 1)->select();
        foreach ($tableList as $table) {
            $table->fields;
        }

        return $tableList;
    }

    // 获取工作表条目标签列表
    public static function getItemLabelList($itemId) {
        $tableItemLabel = new TableItemLabel();
        $labelList = $tableItemLabel->where('item_id', $itemId)->alias('til')->join('oa_label l', 'til.label_id = l.label_id')->column('label_name');

        return $labelList;
    }
}