<?php
/**
 * Created by PhpStorm.
 * User: Link
 * Date: 2020/11/13
 * Time: 13:46
 */

namespace app\index\model;


use think\Model;

class TableWork extends Model
{
    /**
     * 与表创建人一对一对应
     * @return \think\model\relation\HasOne
     */
    public function creator(){
        return $this -> hasOne('User',"user_id","creator_id")->field('user_name');
    }

    /**
     * 与表字段一对多对应
     * @return \think\model\relation\HasMany
     */
    public function fields(){
        return $this -> hasMany('TableField',"table_id","table_id")->field('field_id,name,type,value,sort,status,show')->order("sort");
    }

    /**
     * 与可见人一对多对应
     * @return \think\model\relation\HasMany
     */
    public function users(){
        return $this -> hasMany('TableUser',"table_id","table_id")->field('user_id');
    }

}