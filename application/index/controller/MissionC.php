<?php

namespace app\index\controller;

use app\common\util\dateUtil;
use app\index\model\Attachment;
use app\index\model\Minute;
use app\index\model\MissionTree;
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

/**
 * 任务控制器
 * Class MissionC
 * @package app\index\controller
 */
class MissionC extends Controller
{
    /**
     * 显示任务列表
     * @param int $page
     * @param int $limit
     * @param string $keyword
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index($page = 1, $limit = 10, $keyword = '')
    {
        $mission = new Mission();
        $userId = Session::get("info")["user_id"];
        // 如果传入关键词、项目代号、标签
        if($keyword != '') {            // 标题
            $mission->where('mission_title','like',"%$keyword%");
        }
        if(input('get.project_id') != '') {            // 关联项目
            $mission->where('project_id',input('get.project_id'));
        }
        if(input('get.label') != '') {          // 标签
            $label = input('get.label');
            $mission->where('label', 'like', "%$label%");
        }
        $count = $mission->where('assignee_id', $userId)->where('status', 'in', '0,1')->count();
        // 如果传入关键词、项目代号、标签 TODO
        if($keyword != '') {            // 标题
            $mission->where('mission_title','like',"%$keyword%");
        }
        if(input('get.project_id') != '') {            // 关联项目
            $mission->where('project_id',input('get.project_id'));
        }
        if(input('get.label') != '') {          // 标签
            $label = input('get.label');
            $mission->where('label', 'like', "%$label%");
        }
        $mission->where('assignee_id', $userId)->where('status', 'in', '0,1');
        if(input('get.field') != '') {          // 排序
            $mission->order(input('get.field') . ' ' . input('get.order'));
        } else {
            $mission->order('priority desc');
        }
        $missions = $mission->field('mission_id,mission_title,reporter_id,status,priority,label,start_date,finish_date')->page("$page, $limit")->select();

        // 处理结果集
        foreach ($missions as $one) {
            $one->reporter_name = $one->reporter->user_name;            // 关联查找用户名
            $one->status = DataEnum::$missionStatus[$one->status];          // 转换状态码成信息
            // 获取最近一条任务处理信息
            if($one->process) {
                $one->process_note = $one->process[0]->process_note;
                $one->process_time = explode(' ', $one->process[0]->process_time)[0];
            } else {
                $one->process_note = '';
                $one->process_time = '';
            }

            unset($one->process);
        }

        return Result::returnResult(Result::SUCCESS, $missions, $count);
    }

    /**
     * 获取选择任务列表
     * @param int $page
     * @param int $limit
     * @param string $keyword
     * @param int $type
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function selectIndex($page = 1, $limit = 10, $keyword = '', $type = 0) {
        $mission = new Mission();
        // 获取查询结果任务数目
        if($keyword != '') {
            $mission->where('mission_id', $keyword)->whereOr('mission_title', 'like', "%$keyword%");
        }
        if($type == 1) {
            $mission->where('minute_id', 0);
        }
        $count = $mission->count();

        // 获取查询结果任务列表
        if($keyword != '') {
            $mission->where('mission_id', $keyword)->whereOr('mission_title', 'like', "%$keyword%");
        }
        if($type == 1) {
            $mission->where('minute_id', 0);
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
     * @param Request $request
     * @return array
     * @throws \think\exception\DbException
     */
    public function save(Request $request)
    {
        $userId = Session::get("info")["user_id"];          // TODO
        $_POST['parent_mission_id'] = input('post.is-root')? -1 : input('post.parent_mission_id');
        // 插入任务信息
        $infoArray = array_merge($_POST, [
            'reporter_id' => $userId,
            'create_time' => date('Y-m-d H:i:s', time())
        ]);
        $mission = new Mission($infoArray);
        $mission->allowField(true)->save();

        // 插入初始任务处理信息
        $missionProcess = new MissionProcess();
        $missionProcess->data([
            'mission_id'  =>  $mission->mission_id,
            'handler_id' =>  $userId,
            'process_note' => '初始处理任务信息',
            'post_assignee_id' => input('post.assignee_id'),
            'post_finish_date' => input('post.finish_date')
        ]);
        $missionProcess->save();

        // 插入任务和关注人对应信息
        if(input('post.invite_follow')) {
            $userIds = explode(',', input('post.invite_follow'));
            foreach ($userIds as $userId) {
                $missionInterest = new MissionInterest();
                $missionInterest->mission_id = $mission->mission_id;
                $missionInterest->user_id = $userId;
                $missionInterest->save();
            }
        }

        // 任务处理关联附件
        if(input('post.attachment_list')) {
            $attachmentIds = explode(';', input('post.attachment_list'));
            foreach ($attachmentIds as $attachmentId) {
                $attachment = Attachment::get($attachmentId);
                $attachment->attachment_type = 'mission';
                $attachment->related_id = $missionProcess->process_id;
                $attachment->save();
            }
        }

        // 发送钉钉消息 TODO

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
        // 判断用户是否有权限查看任务详情 TODO
        // 获取任务详情
        $mission = Mission::get($id);
        // 处理编号为 0 问题
        $mission->parent_mission_id = ($mission->parent_mission_id == 0)? '' : $mission->parent_mission_id;
        $mission->minute_id = ($mission->minute_id == 0)? '' : $mission->minute_id;
        $mission->requirement_id = ($mission->requirement_id == 0)? '' : $mission->requirement_id;
        $mission->problem_id = ($mission->problem_id == 0)? '' : $mission->problem_id;
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
//        foreach ($mission->attachments as $attachment) {
//            $attachment->save_path = DataEnum::uploadDir . $attachment->save_path;
//            $attachment->uploader;
//        }
//        $attachmentList = $mission->attachments;         // 需要定义一个临时变量
//        unset($mission->attachments);

        // 获取任务处理记录
        foreach ($mission->processList as $process) {
            $process->handler;
            $process->status;
            $tempArray = array();
            // 整合任务处理附件到任务任务附件列表
            foreach ($process->attachments as $attachment) {
                $attachment->save_path = DataEnum::uploadDir . $attachment->save_path;
//                $attachment->uploader;
//                array_push($attachmentList, $attachment);
//                array_push($tempArray, $attachment->source_name);
            }
//            unset($process->attachments);
//            $process->attachment = implode('，', $tempArray);           // 附件信息
        }
//        $mission->attachmentList = $attachmentList;

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
        $sessionUserId = '1110023';
        // 更新任务信息
        $fields = input('put.');
        $fields['parent_mission_id'] = input('put.is_root')? -1 : input('parent_mission_id');
        $mission->allowField(true)->save($fields);

        // 处理关注人列表
        if(input('put.invite_follow')) {
            $userIds = explode(',', input('put.invite_follow'));
            // 获取关注人工号列表数组
            $missionInterest = new MissionInterest();
            $interestUserIds = $missionInterest->where('mission_id', $id)->column('user_id');
            foreach ($userIds as $userId) {
                // 如果未存在 任务-关注人 对应关系就插入
                if(!in_array($userId, $interestUserIds)) {
                    $missionInterest = new MissionInterest();
                    $missionInterest->mission_id = $id;
                    $missionInterest->user_id = $userId;
                    $missionInterest->save();
                }
            }
        }

        // 插入任务处理记录
        if(input('put.process_note') != '' || input('put.attachment_list') != '') {
            $missionProcess = new MissionProcess();
            $missionProcess->data([
                'mission_id'  =>  $id,
                'handler_id' =>  $sessionUserId,
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
                    $attachment->attachment_type = 'mission';
                    $attachment->related_id = $missionProcess->process_id;
                    $attachment->save();
                }
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

    // 修改任务优先级
    public function modifyPriority($id)
    {
        $mission = Mission::get($id);

        if(input('post.type') == 'reduce') {
            $mission->priority = ($mission->priority == 0)? 0 : $mission->priority - 1;
        } else {
            $mission->priority += 1;
        }
        $mission->save();

        return Result::returnResult(Result::SUCCESS);
    }

    // 获取关注的根任务列表
    public function treeIndex($page = 1, $limit = 10)
    {
        $mission = new Mission();
        $userId = '1110023';
        // 如果传入关键词、项目代号、标签、根任务
        if(input('get.project_id') != '') {            // 关联项目
            $mission->where('project_id',input('get.project_id'));
        }
        if(input('get.label') != '') {          // 标签
            $label = input('get.label');
            $mission->where('label', 'like', "%$label%");
        }
        $count = $mission->where('parent_mission_id', -1)->alias('m')->join('oa_mission_interest mi',"mi.user_id= '$userId' and m.mission_id = mi.mission_id")->count();
        // 如果传入关键词、项目代号、标签、根任务 TODO
        if(input('get.project_id') != '') {            // 关联项目
            $mission->where('project_id',input('get.project_id'));
        }
        if(input('get.label') != '') {          // 标签
            $label = input('get.label');
            $mission->where('label', 'like', "%$label%");
        }
        $mission->where('parent_mission_id', -1);
        if(input('get.field') != '') {          // 排序
            $mission->order(input('get.field') . ' ' . input('get.order'));
        } else {
            $mission->order('mission_id desc');
        }
        $missions = $mission->alias('m')->join('oa_mission_interest mi',"mi.user_id= '$userId' and m.mission_id = mi.mission_id")->field('m.mission_id,mission_title,assignee_id,status,project_id,label')->page("$page, $limit")->select();

        // 处理结果集
        foreach ($missions as $one) {
            $one->assignee_name = $one->assignee->user_name;            // 关联处理人
            $one->status = $one->missionStatus->status_name;          // 转换状态码成信息
            $one->project_id = $one->project->project_code;            // 关联项目
            unset($one->assignee, $one->missionStatus, $one->project);
        }

        return Result::returnResult(Result::SUCCESS, $missions, $count);
    }

    // 获取任务树时间戳列表
    public function getTreeRecordList($id) {
        // 判断任务是否为根任务
        $mission = Mission::get($id);
        if($mission->parent_mission_id != -1) {
            return Result::returnResult(Result::NOT_ROOT_MISSION);
        }

        $missionTree = new MissionTree();
        $recordDate = $missionTree->where('root_mission_id', $id)->field('distinct record_date')->select();

        return Result::returnResult(Result::SUCCESS, $recordDate);
    }

    // 盖时间戳
    public function recordTree($id) {
        // 验证是否有管理员权限 TODO
        $record_date = date('Y-m-d',time());
        $rootMission = Mission::where('mission_id', $id)->field('mission_id,mission_title,assignee_id,description,status,finish_date,parent_mission_id')->find();
        // 获取子任务树列表
        $missionService = new MissionService();
        $missionTree = $missionService->getMissionTree($id);
        array_unshift($missionTree, $rootMission);

        foreach ($missionTree as $mission) {
            $treeModel = new MissionTree();
            $create_time = date('Y-m-d H:i:s',time());
            // 如果任务已在今日记录里
            $treeMission = $treeModel->where('mission_id', $mission->mission_id)->where('root_mission_id', $rootMission->mission_id)->where('record_date', $record_date)->find();
            if($treeMission) {
                $tempModel = $treeMission;
            } else {
                $tempModel = $treeModel;
            }
            $tempModel->data([
                'mission_id'  =>  $mission->mission_id,
                'mission_title' =>  $mission->mission_title,
                'assignee_id' => $mission->assignee_id,
                'status' => $mission->status,
                'finish_date' => $mission->finish_date,
                'root_mission_id' => $rootMission->mission_id,
                'record_date' => $record_date,
                'create_time' => $create_time,
            ]);
            $tempModel->save();
        }

        return Result::returnResult(Result::SUCCESS);
    }

    // 获取任务树进展详情
    public function getProgressReport($id = 12, $date = '2020-10-09') {
        $treeModel = new MissionTree();
        $changeList = array();            // 信息变化的任务列表
        $addList = array();            // 增加的任务列表
        $deleteList = array();            // 删除的任务列表

        // 获取指定日期任务树信息
        $treeRecord = $treeModel->where('root_mission_id', $id)->where('record_date', $date)->field('mission_id,mission_title,assignee_id,status,finish_date')->select();
        // 获取当前任务树信息
        $missionService = new MissionService();
        $treeNow = $missionService->getMissionTree($id);

        foreach ($treeNow as $missionNow) {
            $new = 1;
            foreach ($treeRecord as $missionRecord) {
                if($missionRecord->mission_id == $missionNow->mission_id) {
                    $new = 0;
                    $change = 0;
                    // 如果处理人、任务状态、截止时间有变化
                    if($missionRecord->assignee_id != $missionNow->assignee_id) {
                        $missionNow->assignee_change = [$missionRecord->assignee->user_name, $missionNow->assignee->user_name];
                        $change = 1;
                    } else {
                        $missionNow->assignee_change = '';
                    }
                    if($missionRecord->status != $missionNow->status) {
                        $missionNow->status_change = [$missionRecord->missionStatus->status_name, $missionNow->missionStatus->status_name];
                        $change = 1;
                    } else {
                        $missionNow->status_change = '';
                    }
                    if($missionRecord->finish_date != $missionNow->finish_date) {
                        $missionNow->date_change = [$missionRecord->finish_date, $missionNow->finish_date];
                        $change = 1;
                    } else {
                        $missionNow->date_change = '';
                    }
                    if($change == 1) {
                        array_push($changeList, $missionNow);
                    }
                    break;
                }
            }
            if($new == 1) {          // 如果是增加的任务
                $missionNow->type = 'add';
                array_push($addList, $missionNow);
            }
        }
        foreach ($treeRecord as $missionRecord) {
            $delete = 1;
            foreach ($treeNow as $missionNow) {
                if($missionNow->mission_id == $missionRecord->mission_id) {         // 如果是删除的任务
                    $delete = 0;
                    break;
                }
            }
            if($delete == 1) {
                $missionNow->type = 'delete';
                array_push($deleteList, $missionNow);
            }
        }

        $data = [
            'changeList' => collection($changeList)->visible(['mission_id','mission_title','assignee_change','status_change','date_change'])->toArray(),
            'addList' => collection($addList)->visible(['mission_id','mission_title','type'])->toArray(),
            'deleteList' => collection($deleteList)->visible(['mission_id','mission_title','type'])->toArray()
        ];
        return Result::returnResult(Result::SUCCESS, $data);
    }

    // 获取任务树列表
    public function missionTree($id) {
        $missionService = new MissionService();
        $missionTree = $missionService->getMissionTree($id);

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

    // 关注任务树所有任务
    public function interestTree() {
        $missionId = input('post.missionId');
        $userIds = explode(',', input('post.userIds'));
        $type = input('post.type');

        // 获取任务树列表
        $missionService = new MissionService();
        $missionTree = $missionService->getMissionTree($missionId);

        foreach ($missionTree as $mission) {
            foreach ($userIds as $userId) {
                $missionInterest = MissionInterest::get(['mission_id' => $mission->mission_id, 'user_id' => $userId]);
                if ($type == 'allInterest') {
                    if (!$missionInterest) {
                        $missionInterest = new MissionInterest();

                        $missionInterest->mission_id = $mission->mission_id;
                        $missionInterest->user_id = $userId;
                        $missionInterest->save();
                    }
                } else {
                    if ($missionInterest) {
                        $missionInterest->delete();
                    }
                }
            }
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
