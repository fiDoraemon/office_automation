<?php
/**
 * Created by PhpStorm.
 * User: Link
 * Date: 2020/10/22
 * Time: 17:00
 */

namespace app\index\model;


use think\Model;

class DocReviewer extends Model
{
    /**
     * 与员工一对一关联
     * @return \think\model\relation\HasOne
     */
    public function user(){
        return $this->hasOne('User',"user_id","user_id")->field('user_name');
    }
}