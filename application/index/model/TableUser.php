<?php

namespace app\index\model;

use think\Model;

class TableUser extends Model
{
    public function user(){
        return $this -> hasOne('User',"user_id","user_id")->field('user_name');
    }
}
