<?php

namespace app\index\model;

use think\Model;

class MissionTree extends Model
{
    // 关联处理人（一对一）
    public function assignee()
    {
        return $this->hasOne('User','user_id', 'assignee_id')->field('user_name');
    }

    // 关联状态信息（一对一）
    public function missionStatus()
    {
        return $this->hasOne('MissionStatus', 'status_id', 'status')->field('status_name');
    }
}
