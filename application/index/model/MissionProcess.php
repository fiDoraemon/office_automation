<?php

namespace app\index\model;

use think\Model;

class MissionProcess extends Model
{
    // 处理人（一对一）
    public function handler()
    {
        return $this->hasOne('User','user_id', 'handler_id')->field('user_name');
    }

    // 后续处理人（一对一）
    public function assignee()
    {
        return $this->hasOne('User','user_id', 'post_assignee_id')->field('user_name');
    }

    // 任务状态（一对一）
    public function status()
    {
        return $this->hasOne('MissionStatus','status_id', 'post_status')->field('status_name');
    }

    // 关联附件（一对多）
    public function attachments()
    {
        return $this->hasMany('Attachment', 'related_id', 'process_id');
    }
}
