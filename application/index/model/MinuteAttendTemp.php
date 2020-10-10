<?php
/**
 * Created by PhpStorm.
 * User: Link
 * Date: 2020/10/9
 * Time: 13:57
 */

namespace app\index\model;


use think\Model;

class MinuteAttendTemp extends Model
{
    public function user()
    {
        return $this->hasOne('User',"user_id","user_id")->field('user_id,user_name');
    }
}