<?php
/**
 * Created by PhpStorm.
 * User: Link
 * Date: 2020/10/23
 * Time: 10:09
 */

namespace app\index\model;


use think\Model;

class DocRequest extends Model
{
    /**
     * 与发起人一对一关联
     * @return \think\model\relation\HasOne
     */
    public function requestUser(){
        return $this->hasOne('User',"user_id","author_id")->field('user_name');
    }

    /**
     * 与审批人一对一关联
     * @return \think\model\relation\HasOne
     */
    public function approverUser(){
        return $this->hasOne('User',"user_id","approver_id")->field('user_name');
    }

    /**
     * 与项目一对一对应
     * @return \think\model\relation\HasOne
     */
    public function projectCode(){
        return $this->hasOne('Project',"project_id","project_id")->field('project_code');
    }

    /**
     * 与项目状态一对一对应
     * @return \think\model\relation\HasOne
     */
    public function projectStage(){
        return $this->hasOne('ProjectStage',"id","stage")->field('stage_name');
    }

    /**
     * 与附件一对多对应
     */
    public function attachments(){
        return $this->hasMany('Attachment', 'related_id', 'request_id')->where('attachment_type', 'doc')->field('attachment_id, source_name, storage_name, uploader_id, file_size, save_path');
    }
}