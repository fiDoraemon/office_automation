<?php

namespace app\index\controller;

use app\common\model\Attachment;
use app\common\service\AttachmentService;
use app\index\common\DataEnum;
use app\index\model\Mission;
use app\index\model\MissionInterest;
use app\index\model\MissionProcess;
use app\index\model\MissionStatus;
use app\index\service\ProjectService;
use app\common\Result;
use think\Controller;
use think\Request;
use think\Session;

class MissionC extends Controller
{
    /**
     * 显示任务列表
     *
     * @return \think\Response
     */
    public function index($page = 1, $limit = 10, $keyword = '')
    {
        $mission = new Mission();
        // 如果传入关键词、项目代号、标签
        if($keyword != '') {
            $mission->where('mission_title','like',"%$keyword%");
        }
        if(input('get.related_project') != '') {
            $mission->where('related_project',input('get.related_project'));
        }
        if(input('get.label') != '') {
            $mission->where('label',input('get.label'));
        }
        if(input('get.field') != '') {
            $mission->order(input('get.field') . ' ' . input('get.order'));
        }
        $count = $mission->count();
        // 如果传入关键词、项目代号、标签 TODO
        if($keyword != '') {
            $mission->where('mission_title','like',"%$keyword%");
        }
        if(input('get.related_project') != '') {
            $mission->where('related_project',input('get.related_project'));
        }
        if(input('get.label') != '') {
            $mission->where('label',input('get.label'));
        }
        if(input('get.field') != '') {
            $mission->order(input('get.field') . ' ' . input('get.order'));
        }
        $missions = $mission->field('mission_id,mission_title,reporter_id,status,priority,label,start_date,finish_date')->page("$page, $limit")->select();

        // 处理结果集
        Session::set('user_type', 'reporter_id');
        foreach ($missions as $one) {
            $one->reporter_name = $one->reporter->user_name;            // 关联查找用户名
            $one->status = DataEnum::$missionStatus[$one->status];          // 转换状态码成信息
        }

        return Result::returnResult(Result::SUCCESS, $missions, $count);
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        //
    }

    /**
     * 保存新建的任务
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        // 插入任务信息
        $infoArray = array_merge($_POST, [
            'reporter_id' => '1110023',          // TODO
            'create_time' => date('Y-m-d H:i:s', time())
        ]);
        $mission = new Mission($infoArray);
        $mission->allowField(true)->save();

        // 插入任务和关注人对应信息
        $userIds = explode(';', input('post.invite_follow_ids'));
        foreach ($userIds as $userId) {
            $missionInterest = new MissionInterest();
            $missionInterest->mission_id = $mission->mission_id;
            $missionInterest->user_id = $userId;
            $missionInterest->save();
        }

        // 任务关联附件
        $attachmentIds = explode(';', input('post.attachment_list'));
        foreach ($attachmentIds as $attachmentId) {
            $attachment = Attachment::get($attachmentId);
            $missionInterest->attachment_type = 'mission';
            $missionInterest->mission_id = $mission->mission_id;
            $missionInterest->save();
        }

        return Result::returnResult(Result::SUCCESS);
    }

    /**
     * 显示指定的任务
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        // 获取任务详情
        $mission = Mission::get($id);
        // 关联查找用户名和转换状态码成信息
        $mission->reporter_id = $mission->reporter->user_name;
        $mission->assignee;
        // 获取任务关注人列表
        $nameArray = array();
        $idArray = array();
        if($mission->missionInterests) {
            foreach ($mission->missionInterests as $missionInterest) {
                array_push($nameArray, $missionInterest->user->user_name);
                array_push($idArray, $missionInterest->user_id);
            }
        }
        $mission->interest_names = implode('，', $nameArray);
        $mission->interest_ids = implode(',', $idArray);
        unset($mission->missionInterests);          // 去除 任务-关注人 关联属性

        // 获取任务附件列表
        $mission->attachmentList = $mission->attachments()->where('attachment_type', 'mission')->select();
        foreach ($mission->attachmentList as $attachment) {
            $attachment->save_path = DataEnum::uploadDir . $attachment->save_path;
            $attachment->uploader;
        }

        // 获取项目列表
        $projectList = ProjectService::index();

        // 获取任务状态列表
        $missionStatus = new MissionStatus();
        $statusList = $missionStatus->field('status_id,status_name')->select();
        $data = [
            'missionDetail' => $mission,
            'projectList' => $projectList,
            'statusList' => $statusList
        ];

        // 获取任务处理记录
        $processList = $mission->processList;
        foreach ($processList as $process) {
            $process->handler;
            $process->assignee;
            $process->status;
        }

        return Result::returnResult(Result::SUCCESS, $data);
    }

    /**
     * 显示编辑资源表单页.
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * 保存更新的任务
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update($id)
    {
        $mission = Mission::get($id);
        $fields = input('post.');
        // 更新任务
        $mission->allowField(true)->save($_POST);

        // 处理关注人列表
        $userIds = explode(';', input('post.invite_follow_ids'));
        // 获取关注人工号列表数组
        $missionInterest = new MissionInterest();
        $interestUserIds = $missionInterest->where('mission_id', 12)->column('user_id');
        foreach ($userIds as $userId) {
            // 如果未存在 任务-关注人 对应关系就插入
            if(in_array($userId, $interestUserIds)) {
                $missionInterest = new MissionInterest();
                $missionInterest->mission_id = $id;
                $missionInterest->user_id = $userId;
                $missionInterest->save();
            }
        }

        // 插入任务处理记录
        $missionProcess = new MissionProcess();
        $missionProcess->data([
            'mission_id'  =>  $id,
            'handler_id' =>  '1110023',          // TODO
            'process_note' => $fields['process_note'],
            'post_assignee_id' => $fields['assignee_id'],
            'post_status' => $fields['status'],
            'post_finish_date' => $fields['finish_date']
        ]);
        $missionProcess->save();

        // 任务处理关联附件
        $attachmentIds = explode(';', input('post.attachment_list'));
        foreach ($attachmentIds as $attachmentId) {
            $attachment = Attachment::get($attachmentId);
            $attachment->attachment_type = 'mission_process';
            $attachment->related_id = $missionProcess->process_id;
        }

        return Result::returnResult(Result::SUCCESS);
    }

    /**
     * 删除指定资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function delete($id)
    {
        //
    }

    // 删除指定附件
    public function deleteAttachment()
    {
        // 删除附件信息
        $result = AttachmentService::delete(input('post.attachemnt_id'));
        if($result == true) {
            return Result::returnResult(Result::SUCCESS);
        } else {

        }
    }
}
