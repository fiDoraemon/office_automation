<?php

namespace app\index\model;

use think\Model;

class MissionInterest extends Model
{
    // 关联用户（一对一）
    public function user()
    {
        return $this->hasOne('app\common\model\User','user_id', 'user_id')->field('user_name');
    }
}
