<?php

namespace app\index\model;

use think\Model;

class TableItem extends Model
{
    // 关联工作表（一对一）
    public function table(){
        return $this->hasOne('TableWork',"table_id","table_id")->field('table_name');
    }

    // 关联条目处理（一对多）
    public function processList()
    {
        return $this->hasMany('TableItemProcess','item_id','item_id')->alias('tip')->join('oa_user u', 'tip.handler_id = u.user_id')->field('process_id,user_name as handler,process_note,process_time');
    }

    // 关联任务（一对多）：不行
    public function missions()
    {
        return $this->hasMany('Mission','item_id','item_id')->limit(20)->field('mission_id,mission_title,assignee_id,status,finish_date');
    }
}
