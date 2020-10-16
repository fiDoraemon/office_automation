<?php

namespace app\index\model;

use think\Model;

class MissionView extends Model
{
    // 关联任务（一对一）
    public function mission()
    {
        return $this->hasOne('Mission','mission_id', 'mission_id')->field('mission_title');
    }
}
