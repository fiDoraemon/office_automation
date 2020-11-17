<?php

namespace app\index\model;

use think\Model;

class TableItem extends Model
{
    // 关联工作表（一对一）
    public function table(){
        return $this->hasOne('TableWork',"table_id","table_id")->field('table_name');
    }
    // 关联全部字段（一对多）
    public function fields()
    {
        return $this->hasMany('TableFieldValue','item_id','item_id')->where('status', 1)->alias('tfv')->join('oa_table_field tf', 'tfv.field_id = tf.field_id')->field('tf.field_id,name,type,value,field_value');
    }

    // 关联部分字段（一对多）
    public function partFields()
    {
        return $this->hasMany('TableFieldValue','item_id','item_id')->where('status', 1)->limit(3)->alias('tfv')->join('oa_table_field tf', 'tfv.field_id = tf.field_id')->field('tf.field_id,name,type,value,field_value');
    }

    // 关联条目处理（一对多）
    public function processList()
    {
        return $this->hasMany('TableItemProcess','item_id','item_id')->alias('tip')->join('oa_user u', 'tip.handler_id = u.user_id')->field('process_id,user_name as handler,process_note,process_time');
    }
}
