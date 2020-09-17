<?php

namespace app\index\model;

use think\Model;
use think\Session;

class Mission extends Model
{
    // 关联用户
    public function user()
    {
        return $this->hasOne('app\common\model\User','user_id', Session::get('user_type'))->field('user_name');
    }
}
