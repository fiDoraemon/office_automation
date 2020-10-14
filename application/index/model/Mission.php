<?php

namespace app\index\model;

use think\Model;

class Mission extends Model
{
    // 关联发起人（一对一）
    public function reporter()
    {
        return $this->hasOne('User','user_id', 'reporter_id')->field('user_name,dd_userid');
    }

    // 关联处理人（一对一）
    public function assignee()
    {
        return $this->hasOne('User','user_id', 'assignee_id')->field('user_name,dd_userid');
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

    // 关联任务处理（一对多,但是只要获取最新的一个消息）
    public function process()
    {
        return $this->hasMany('MissionProcess', 'mission_id', 'mission_id')->order("process_id","desc")->limit(1)->field('process_note,process_time');
    }

    // 关联附件（一对多）
//    public function attachments()
//    {
//        return $this->hasMany('Attachment', 'related_id', 'mission_id')->where('attachment_type', 'mission')->field('attachment_id, source_name, uploader_id, file_size, save_path, upload_date');
//    }

    // 关联项目代号（一对一）
    public function project()
    {
        return $this->hasOne('Project', 'project_id', 'project_id')->field('project_code');
    }

    // 关联状态信息（一对一）
    public function missionStatus()
    {
        return $this->hasOne('MissionStatus', 'status_id', 'status')->field('status_name');
    }
}
