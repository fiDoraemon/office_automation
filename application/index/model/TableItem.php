<?php

namespace app\index\model;

use think\Model;

class TableItem extends Model
{
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
}
