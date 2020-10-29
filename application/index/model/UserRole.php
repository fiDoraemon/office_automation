<?php
/**
 * Created by PhpStorm.
 * User: Link
 * Date: 2020/10/29
 * Time: 10:40
 */

namespace app\index\model;


use think\Model;

class UserRole extends Model
{
    /**
     * 与员工一对一关联
     * @return \think\model\relation\HasOne
     */
    public function user(){
        return $this->hasOne('User',"user_id","user_id")->field('user_name');
    }
}