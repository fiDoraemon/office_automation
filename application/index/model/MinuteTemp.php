<?php
/**
 * Created by PhpStorm.
 * User: Link
 * Date: 2020/9/30
 * Time: 16:20
 */

namespace app\index\model;


use think\Model;

class MinuteTemp extends Model
{
    /**
     * 与发起会议的所属部门一对一对应
     * @return \think\model\relation\HasOne
     */
    public function department(){
        return $this->hasOne('Department',"department_id","department_id")->field('department_name');
    }

    /**
     * 与项目状态一对一对应
     * @return \think\model\relation\HasOne
     */
    public function projectStage(){
        return $this->hasOne('ProjectStage',"id","project_stage")->field('id,stage_name');
    }

    /**
     * 与会议发起人一对一关联
     * @return \think\model\relation\HasOne
     */
    public function user(){
        return $this->hasOne('User',"user_id","host_id")->field('user_id,user_name');
    }

    /**
     * 与新增会议页面应到人员会议表一对多关联
     * @return \think\model\relation\HasMany
     */
    public function minuteNewAttends()
    {
        return $this->hasMany('MinuteAttendTemp','minute_id','id')->field('user_id');
    }

    /**
     * 与修改会议页面临时保存的应到人员会议表一对多关联
     * @return \think\model\relation\HasMany
     */
    public function minuteAttends()
    {
        return $this->hasMany('MinuteAttendTemp','minute_id','minute_id')->field('user_id');
    }

    /**
     * 修改会议页面真实应到人员会议表一对多关联
     * 修改会议页面需要查询已经提交了的应到会人员
     * @return \think\model\relation\HasMany
     */
    public function minuteReallyAttends()
    {
        return $this->hasMany('MinuteAttend','minute_id','minute_id')->field('user_id');
    }

    /**
     * 与临实际到会人员一对多关联
     * @return \think\model\relation\HasMany
     */
    public function minuteAttendeds()
    {
        return $this->hasMany('MinuteAttendTemp','minute_id','minute_id')-> where("status",1)->field('user_id');
    }

    /**
     * 与真实的实际到会人员一对多关联
     * @return \think\model\relation\HasMany
     */
    public function minuteReallyAttendeds()
    {
        return $this->hasMany('MinuteAttend','minute_id','minute_id')-> where("status",1)->field('user_id');
    }

    /**
     * 与关联任务一对多对应
     */
    public function minuteMission(){
        return $this->hasMany('Mission',"minute_id","minute_id")->field('mission_id,mission_title,assignee_id,finish_date,status');
    }

    /**
     * 与临时关联任务一对多对应
     */
    public function minuteTempMission(){
        return $this->hasMany('MissionTemp',"minute_id","minute_id")->field('mission_id,mission_title,assignee_id,finish_date,description,new_temp_list');
    }

    /**
     * 与附件一对多对应
     */
    public function attachments(){
        return $this->hasMany('Attachment', 'related_id', 'minute_id')->where('attachment_type', 'minute')->field('attachment_id, source_name, uploader_id, file_size, save_path, upload_date');
    }
}