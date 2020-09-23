<?php

namespace app\index\model;

use think\Model;
use think\Session;

class Mission extends Model
{
    // 关联用户（一对一）
    public function reporter()
    {
        return $this->hasOne('User','user_id', 'reporter_id')->field('user_name');
    }

    public function assignee()
    {
        return $this->hasOne('User','user_id', 'assignee_id')->field('user_name');
    }

    // 关联关注人（一对多）
    public function missionInterests()
    {
        return $this->hasMany('MissionInterest','mission_id', 'mission_id')->field('user_id');
    }

    // 关联任务处理（一对多）
    public function processList()
    {
        return $this->hasMany('MissionProcess', 'mission_id', 'mission_id')->field('process_id, handler_id, process_note, post_status, post_finish_date, process_time');
    }

    // 关联附件（一对多）
    public function attachments()
    {
        return $this->hasMany('Attachment', 'related_id', 'mission_id')->field('attachment_id, source_name, uploader_id, file_size, save_path, upload_date');
    }
}
