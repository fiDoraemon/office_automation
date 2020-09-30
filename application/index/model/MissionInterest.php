<?php

namespace app\index\model;

use think\Model;

class MissionInterest extends Model
{
    // 关联用户（一对一）
    public function user()
    {
        return $this->hasOne('User','user_id', 'user_id')->field('user_name');
    }

    // 关联任务（一对一）
    public function mission()
    {
        return $this->hasOne('Mission','mission_id', 'mission_id')->field('mission_id,mission_title,reporter_id,assignee_id,status,finish_date');
    }
}
