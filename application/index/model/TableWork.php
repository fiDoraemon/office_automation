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
}