<?php
/**
 * Created by PhpStorm.
 * User: Link
 * Date: 2020/10/10
 * Time: 17:24
 */

namespace app\index\model;


use think\Model;

class MissionTemp extends Model
{
    // 关联发起人（一对一）
    public function reporter()
    {
        return $this->hasOne('User','user_id', 'reporter_id')->field('user_name');
    }

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
}