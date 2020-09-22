<?php
/**
 * Created by PhpStorm.
 * User: Link
 * Date: 2020/9/17
 * Time: 10:23
 */

namespace app\index\model;


use think\Model;

class MinuteAttend extends Model
{
    protected $pk = 'id';

    public function user()
    {
        return $this->hasOne('User',"user_id","user_id")->field('user_id,user_name');
    }
}