<?php

namespace app\index\controller;

use app\common\util\dateUtil;
use app\index\model\Attachment;
use app\index\model\Minute;
use app\index\service\MissionService;
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
        $userId = '1110023';          // TODO
        // 如果传入关键词、项目代号、标签
        if($keyword != '') {            // 标题
            $mission->where('mission_title','like',"%$keyword%");
        }
        if(input('get.related_project') != '') {            // 关联项目
            $mission->where('related_project',input('get.related_project'));
        }
        if(input('get.label') != '') {          // 标签
            $label = input('get.label');
            $mission->where('label', 'like', "%$label%");
        }
        if(input('get.field') != '') {          // 分页
            $mission->order(input('get.field') . ' ' . input('get.order'));
        }
        $count = $mission->count();
        // 如果传入关键词、项目代号、标签 TODO
        if($keyword != '') {            // 标题
            $mission->where('mission_title','like',"%$keyword%");
        }
        if(input('get.related_project') != '') {            // 关联项目
            $mission->where('related_project',input('get.related_project'));
        }
        if(input('get.label') != '') {          // 标签
            $label = input('get.label');
            $mission->where('label', 'like', "%$label%");
        }
        if(input('get.field') != '') {          // 分页
            $mission->order(input('get.field') . ' ' . input('get.order'));
        }
        $missions = $mission->field('mission_id,mission_title,reporter_id,status,priority,label,start_date,finish_date')->page("$page, $limit")->select();

        // 处理结果集
        foreach ($missions as $one) {
            $one->reporter_name = $one->reporter->user_name;            // 关联查找用户名
            $one->status = DataEnum::$missionStatus[$one->status];          // 转换状态码成信息
        }

        return Result::returnResult(Result::SUCCESS, $missions, $count);
    }

    // 获取选择任务列表
    public function selectIndex($page = 1, $limit = 10, $keyword = '') {
        $mission = new Mission();
        // 获取查询结果任务数目
        if($keyword == '') {
            $mission->where('mission_id', $keyword)->whereOr('mission_title', 'like', "%$keyword%");
        }
        $count = $mission->count();

        // 获取查询结果任务列表
        if($keyword == '') {
            $mission->where('mission_id', $keyword)->whereOr('mission_title', 'like', "%$keyword%");
        }
        $missions = $mission->field('mission_id,mission_title,assignee_id')
            ->order('mission_id desc')
            ->page("$page, $limit")
            ->select();
        foreach ($missions as $one) {
            $one->assignee_name = $one->assignee->user_name;            // 关联处理人
            unset($one->assignee);
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
        if(input('post.invite_follow_ids')) {
            $userIds = explode(';', input('post.invite_follow_ids'));
            foreach ($userIds as $userId) {
                $missionInterest = new MissionInterest();
                $missionInterest->mission_id = $mission->mission_id;
                $missionInterest->user_id = $userId;
                $missionInterest->save();
            }
        }

        // 任务关联附件
        if(input('post.attachment_list')) {
            $attachmentIds = explode(';', input('post.attachment_list'));
            foreach ($attachmentIds as $attachmentId) {
                $attachment = Attachment::get($attachmentId);
                $attachment->attachment_type = 'mission';
                $attachment->related_id = $mission->mission_id;
                $attachment->save();
            }
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

        // 获取任务附件列表
        foreach ($mission->attachments as $attachment) {
            $attachment->save_path = DataEnum::uploadDir . $attachment->save_path;
            $attachment->uploader;
        }
        $attachmentList = $mission->attachments;         // 需要定义一个临时变量
        unset($mission->attachments);

        // 获取任务处理记录
        foreach ($mission->processList as $process) {
            $process->handler;
            $process->status;
            $tempArray = array();
            // 整合任务处理附件到任务任务附件列表
            foreach ($process->attachments as $attachment) {
                $attachment->save_path = DataEnum::uploadDir . $attachment->save_path;
                $attachment->uploader;
                array_push($attachmentList, $attachment);
                array_push($tempArray, $attachment->source_name);
            }
            unset($process->attachments);
            $process->attachment = implode('，', $tempArray);           // 附件信息
        }
        $mission->attachmentList = $attachmentList;

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
        $fields = input('put.');
        // 更新任务
        $mission->allowField(true)->save($_POST);

        // 处理关注人列表
        if(input('put.invite_follow_ids')) {
            $userIds = explode(';', input('put.invite_follow_ids'));
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
        }

        // 插入任务处理记录
        $missionProcess = new MissionProcess();
        $missionProcess->data([
            'mission_id'  =>  $id,
            'handler_id' =>  '1110023',          // TODO
            'process_note' => input('put.process_note'),
            'post_assignee_id' => input('put.assignee_id'),
            'post_status' => input('put.status'),
            'post_finish_date' => input('put.finish_date')
        ]);
        $missionProcess->save();

        // 任务处理关联附件
        if(input('put.attachment_list')) {
            $attachmentIds = explode(';', input('put.attachment_list'));
            foreach ($attachmentIds as $attachmentId) {
                $attachment = Attachment::get($attachmentId);
                $attachment->attachment_type = 'mission_process';
                $attachment->related_id = $missionProcess->process_id;
                $attachment->save();
            }
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

    // 获取根任务列表
    public function treeIndex($page = 1, $limit = 10)
    {
        $mission = new Mission();
        // 如果传入关键词、项目代号、标签、根任务
        if(input('get.related_project') != '') {            // 关联项目
            $mission->where('related_project',input('get.related_project'));
        }
        if(input('get.label') != '') {          // 标签
            $label = input('get.label');
            $mission->where('label', 'like', "%$label%");
        }
        if(input('get.is_root') == 1) {         // 根任务
            $mission->where('parent_mission_id', -1);
        }
        if(input('get.field') != '') {          // 分页
            $mission->order(input('get.field') . ' ' . input('get.order'));
        }
        $count = $mission->where('parent_mission_id', -1)->count();
        // 如果传入关键词、项目代号、标签、根任务 TODO
        $mission->where('parent_mission_id', -1);
        if(input('get.related_project') != '') {            // 关联项目
            $mission->where('related_project',input('get.related_project'));
        }
        if(input('get.label') != '') {          // 标签
            $label = input('get.label');
            $mission->where('label', 'like', "%$label%");
        }
        if(input('get.is_root') == 1) {         // 根任务
            $mission->where('parent_mission_id', -1);
        }
        if(input('get.field') != '') {          // 分页
            $mission->order(input('get.field') . ' ' . input('get.order'));
        }
        $missions = $mission->field('mission_id,mission_title,assignee_id,status,related_project,label')->page("$page, $limit")->select();

        // 处理结果集
        foreach ($missions as $one) {
            $one->assignee_name = $one->assignee->user_name;            // 关联处理人
            $one->status = $one->missionStatus->status_name;          // 转换状态码成信息
            $one->related_project = $one->project->project_code;            // 关联项目
            unset($one->assignee, $one->missionStatus, $one->project);
        }

        return Result::returnResult(Result::SUCCESS, $missions, $count);
    }

    // 获取任务树列表
    public function missionTree($id) {
        $rootMission = Mission::where('mission_id', $id)->field('mission_id,mission_title,assignee_id,status,finish_date,parent_mission_id')->find();
        // 关联处理
        $rootMission->assignee_name = $rootMission->assignee->user_name;
        $rootMission->status = $rootMission->missionStatus->status_name;
        unset($rootMission->assignee, $rootMission->missionStatus);

        // 获取子任务树列表
        $missionService = new MissionService();
        $missionTree = $missionService->getMissionTree($id);
        array_unshift($missionTree,$rootMission);

        return Result::returnResult(Result::SUCCESS, $missionTree, count($missionTree));
    }

    // 删除任务树任务
    public function deleteTreeMission($id) {
        // 获取子任务列表
        $mission = new Mission();
        $childMissionList = $mission->where('parent_mission_id', $id)->field('mission_id,mission_title,assignee_id,status,finish_date,parent_mission_id')->select();

        if($childMissionList) {
            return Result::returnResult(Result::FORBID_DELETE_PARENT);
        } else {
            $mission = $mission->where('mission_id', $id)->find();
            $mission->parent_mission_id = 0;
            $mission->save();

            return Result::returnResult(Result::SUCCESS);
        }
    }

    /** 添加任务树任务
     * @param $id
     * @return array
     * @throws \think\exception\DbException
     */
    public function addTreeMission($id)
    {
        $mission = Mission::get($id);
        $type = input('post.type');

        // 位置
        if(input('post.position') == 'sibling') {
            $id = $mission->parent_mission_id;
        }
        // 方式
        if($type == 'new') {            // 新增任务
            $minute_id = input('post.minute_id')? input('post.minute_id') : 0;
            $infoArray = array_merge($_POST, [
                'reporter_id' => '1110023',          // TODO
                'minute_id' => $minute_id,
                'parent_mission_id' => $id,
                'create_time' => date('Y-m-d H:i:s', time())
            ]);
            $mission = new Mission($infoArray);
            $mission->allowField(true)->save();
        } else if($type == 'exist') {           // 已存在任务
            $existMission = Mission::get(input('post.mission_id'));
            if($existMission->parent_mission_id != 0) {
                return Result::returnResult(Result::PARENT_EXIST);
            }
            $existMission->parent_mission_id = $id;
            $existMission->save();
        } else {            // 从会议信息中导入
            $minute = Minute::get(input('post.minute_id'));
            $mission = new Mission();
            $mission->save([
                'mission_title' => $minute->minute_theme,
                'reporter_id' => '1110023',         // TODO
                'assignee_id' => $minute->host_id,
                'status' => 2,         // 任务状态：已完成 TODO
                'start_date' => date("Y-m-d", time()),
                'finish_date' => $minute->minute_date,
                'description' => $minute->record,
                'minute_id' => input('post.minute_id'),
                'parent_mission_id' => $id
            ]);
        }

        return Result::returnResult(Result::SUCCESS);
    }

    // 获取任务关注人列表
    public function getInterestList($id) {

        $mission = Mission::get($id);
        $nameArray = array();

        if($mission->missionInterests) {
            foreach ($mission->missionInterests as $missionInterest) {
                array_push($nameArray, $missionInterest->user->user_name);
            }
        }
        $interestNames = implode('，', $nameArray);

        return Result::returnResult(Result::SUCCESS, $interestNames);
    }

    /** 获取工作日历任务
     * @param string $type 我头上的、我发起的、我关注的任务
     * @param $offset 月偏移量
     * @return array|false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCalendarMission($type, $offset = 0)
    {
        $mission = new Mission();
        $userId = '1110023';          // TODO
        $dateArray = dateUtil::getMonthFirstAndLast($offset);           // 获取月份第一天和最后一天

        // 我头上的、我发起的、我关注的
        if(input('get.type') == 'assign') {
            $mission->where('assignee_id', $userId);
        } else if(input('get.type') == 'report') {
            $mission->where('reporter_id', $userId);
        } else if(input('get.type') == 'interest') {
            $missionList = array();
            $missionInterest = new MissionInterest();
            $interestList = $missionInterest->where('user_id', $userId)->field('mission_id')->select();
            foreach ($interestList as $interest) {
                $missionInfo = $interest->mission()->where('finish_date', 'between', implode(',', $dateArray))->find();
                if($missionInfo) {
                    // 转换日期格式（不带前导 0）
                    $date = date_create($missionInfo->finish_date);
                    $missionInfo->finish_date = date_format($date,"Y-n-j");
                    array_push($missionList, $missionInfo);
                }
            }

            return Result::returnResult(Result::SUCCESS, $missionList, count($missionList));
        }

        $missions = $mission->where('finish_date', 'between', implode(',', $dateArray))
            ->field('mission_id,mission_title,assignee_id,status,finish_date')
            ->select();

        // 处理结果集
        foreach ($missions as $one) {
            $one->assignee_name = $one->assignee->user_name;            // 关联查找用户名
            $one->status = DataEnum::$missionStatus[$one->status];          // 转换状态码成信息
            unset($one->assignee);
            // 转换日期格式（不带前导 0）
            $date = date_create($one->finish_date);
            $one->finish_date = date_format($date,"Y-n-j");
        }

        return Result::returnResult(Result::SUCCESS, $missions, count($missions));
    }
}
