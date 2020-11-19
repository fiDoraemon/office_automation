<?php

namespace app\index\controller;

use app\common\model\USUser;
use app\common\util\ArrayAndStringUtil;
use app\common\util\curlUtil;
use app\common\util\dateUtil;
use app\index\model\Attachment;
use app\index\model\Data;
use app\index\model\Label;
use app\index\model\Minute;
use app\index\model\MissionLabel;
use app\index\model\MissionTree;
use app\index\model\MissionView;
use app\index\model\User;
use app\index\service\LabelService;
use app\index\service\MissionService;
use app\index\common\DataEnum;
use app\index\model\Mission;
use app\index\model\MissionInterest;
use app\index\model\MissionProcess;
use app\index\model\MissionStatus;
use app\index\service\ProjectService;
use app\common\Result;
use app\index\service\UserService;
use app\common\model\LittleMission;
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
            $mission->alias('m')->join('oa_mission_label ml',"m.mission_id = ml.mission_id")->join('oa_label l',"l.label_id = ml.label_id and label_name like '%$label%'");
        }
        $count = $mission->where('assignee_id', $userId)->where('status', 'in', '0,1')->count();
        // 如果传入关键词、项目代号、标签 TODO
        $mission->alias('m');
        if($keyword != '') {            // 标题
            $mission->where('mission_title','like',"%$keyword%");
        }
        if(input('get.project_id') != '') {            // 关联项目
            $mission->where('project_id',input('get.project_id'));
        }
        if(input('get.label') != '') {          // 标签
            $label = input('get.label');
            $mission->join('oa_mission_label ml',"m.mission_id = ml.mission_id")->join('oa_label l',"l.label_id = ml.label_id and label_name like '%$label%'");

        }
        $mission->where('assignee_id', $userId)->where('status', 'in', '0,1');
        if(input('get.field') != '') {          // 排序
            $mission->order(input('get.field') . ' ' . input('get.order'));
        } else {
            $mission->order('priority desc');
        }
        $missions = $mission->field('m.mission_id,mission_title,reporter_id,status,priority,finish_date')->page("$page, $limit")->select();

        // 处理结果集
        foreach ($missions as $one) {
            $one->reporter_name = $one->reporter->user_name;            // 关联查找用户名
            $one->status = DataEnum::$missionStatus[$one->status];          // 转换状态码成信息
            // 获取最近一条任务处理信息
            $one->process_note = $one->process? $one->process[0]->process_note : '';
            $one->process_time = $one->process? explode(' ', $one->process[0]->process_time)[0] : '';
            unset($one->reporter_id, $one->reporter, $one->process);
            // 获取标签列表
            $one->labelList = LabelService::getMissionLabelList($one->mission_id);
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
     * 获取新建任务页面所需信息
     *
     * @return \think\Response
     */
    public function create()
    {
        $projectList = ProjectService::getProjectList();            // 获取项目列表
        $labelList = LabelService::getLabelList();          // 获取标签列表
        $data = [
            'projectList' => $projectList,
            'labelList' => $labelList
        ];

        return Result::returnResult(Result::SUCCESS, $data);
    }

    /**
     * 搜索任务
     * @param int $page
     * @param int $limit
     * @param string $keyword
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function serach($page = 1, $limit = 10, $keyword = '')
    {
        $mission = new Mission();
        if(input('get.mission_id') == '' && $keyword == '' && input('get.project_id') == '' && input('get.label') == '') {
            return Result::returnResult(Result::SUCCESS, [], 0);
        }
        // 任务和关键词、项目代号、标签不叠加
        if(input('get.mission_id') != '') {            // 任务号
            $mission->where('mission_id',input('get.mission_id'));
        } else {
            if($keyword != '') {            // 标题
                $mission->where('mission_title','like',"%$keyword%");
            }
            if(input('get.project_id') != '') {            // 关联项目
                $mission->where('project_id',input('get.project_id'));
            }
            if(input('get.label') != '') {          // 标签
                $label = input('get.label');
                $mission->alias('m')->join('oa_mission_label ml',"m.mission_id = ml.mission_id")->join('oa_label l',"l.label_id = ml.label_id and label_name like '%$label%'");
            }
        }

        $count = $mission->count();
        // 如果传入关键词、项目代号、标签 TODO
        $mission->alias('m');
        if(input('get.mission_id') != '') {            // 任务号
            $mission->where('mission_id',input('get.mission_id'));
        } else {
            if($keyword != '') {            // 标题
                $mission->where('mission_title','like',"%$keyword%");
            }
            if(input('get.project_id') != '') {            // 关联项目
                $mission->where('project_id',input('get.project_id'));
            }
            if(input('get.label') != '') {          // 标签
                $label = input('get.label');
                $mission->join('oa_mission_label ml',"m.mission_id = ml.mission_id")->join('oa_label l',"l.label_id = ml.label_id and label_name like '%$label%'");
            }
        }
        if(input('get.field') != '') {          // 排序
            $mission->order(input('get.field') . ' ' . input('get.order'));
        } else {
            $mission->order('mission_id desc');
        }
        $missions = $mission->field('m.mission_id,mission_title,reporter_id,assignee_id,status,priority,label,finish_date')->page("$page, $limit")->select();

        // 处理结果集
        foreach ($missions as $one) {
            $one->reporter_name = $one->reporter->user_name;
            $one->assignee_name = $one->assignee->user_name;
            $one->status = MissionStatus::get($one->status)->status_name;
            // 获取最近一条任务处理信息
            $one->process_time = $one->process? explode(' ', $one->process[0]->process_time)[0] : '';
            // 获取标签列表
            $one->labelList = LabelService::getMissionLabelList($one->mission_id);

            // 删除多余字段
            unset($one->reporter_id, $one->assignee_id, $one->reporter, $one->assignee, $one->process);
        }

        return Result::returnResult(Result::SUCCESS, $missions, $count);
    }

    /**
     * 保存新建的任务
     * @param Request $request
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function save(Request $request)
    {
        $sessionUserId = Session::get("info")["user_id"];
        $_POST['parent_mission_id'] = input('post.is-root')? -1 : input('post.parent_mission_id');
        // 插入任务信息
        $infoArray = array_merge($_POST, [
            'reporter_id' => $sessionUserId,
            'create_time' => date('Y-m-d H:i:s', time())
        ]);
        $mission = new Mission($infoArray);
        $mission->allowField(true)->save();
        $this->recordMissionView($mission->mission_id);         // 加进任务浏览记录

        // 插入初始任务处理信息
        $missionProcess = new MissionProcess();
        $missionProcess->data([
            'mission_id'  =>  $mission->mission_id,
            'handler_id' =>  $sessionUserId,
            'process_note' => '初始处理任务信息',
            'post_assignee_id' => input('post.assignee_id'),
            'post_finish_date' => input('post.finish_date')
        ]);
        $missionProcess->save();

        $useridList = '';            // 关注人钉钉 userid 以,分隔字符串
        // 插入任务和关注人对应信息
        if(input('post.invite_follow')) {
            $userIds = explode(',', input('post.invite_follow'));
            $useridArray = array();          // 钉钉 userid 列表

            foreach ($userIds as $userId) {
                if($userId == $mission->reporter_id || $userId == $mission->assignee_id) {          // 过滤发起人和处理人
                    continue;
                }
                $missionInterest = new MissionInterest();
                $missionInterest->mission_id = $mission->mission_id;
                $missionInterest->user_id = $userId;
                $missionInterest->save();

                $user = User::getByUserId($userId);
                if($user->dd_userid != '' && $user->dd_open == 1) {
                    array_push($useridArray, $user->dd_userid);
                }
            }
            $useridList = implode(',', $useridArray);
        }

        // 任务处理关联附件
        $attachmentList = '';           // 附件清单字符串
        if(input('post.attachment_list')) {
            $attachmentIds = explode(';', input('post.attachment_list'));
            $attachmentArray = array();
            foreach ($attachmentIds as $attachmentId) {
                $attachment = Attachment::get($attachmentId);
                $attachment->attachment_type = 'mission';
                $attachment->related_id = $missionProcess->process_id;
                $attachment->save();

                array_push($attachmentArray, $attachment->source_name);
            }
            $attachmentList = implode('，', $attachmentArray);
        }

        // 处理任务标签
        if(input('post.label_list')) {
            $labelList = explode('；', input('post.label_list'));
            foreach ($labelList as $label) {
                $label = Label::get(['label_name' => $label]);
                if(!$label) {
                    $label = new Label();
                    $label->label_name = $label;
                    $label->save();
                }
                $missionLabel = new MissionLabel();
                $missionLabel->mission_id = $mission->mission_id;
                $missionLabel->label_id = $label->label_id;
                $missionLabel->save();
            }
        }

        // 发送钉钉消息(先发送基本信息，再发送链接)
        $data = DataEnum::$msgData;
        $postUrl = 'http://www.bjzzdr.top/us_service/public/other/ding_ding_c/sendMessage';
        $url = 'http://192.168.0.249/office_automation/public/static/layuimini/?missionId=' . $mission->mission_id;
        $templet = '▪ 标题：' . $mission->mission_title . "\n" . '▪ 描述：' . $mission->description . "\n" . "▪ 截止日期：" . $mission->finish_date;
        if($attachmentList != '') {
            $templet .= "\n" . '▪ 附件清单：' . $attachmentList;
        }
        $templet .= "\n" . '▪ 链接：' . $url;
        // 发送给处理人
        if($sessionUserId != input('post.assignee_id') && $mission->assignee->dd_userid != '' && $mission->assignee->dd_open == 1) {
            $data['userList'] = $mission->assignee->dd_userid;
            $message = '◉ 您有新的任务(#' . $mission->mission_id . ')待处理' . "\n" . $templet;
            $data['data']['content'] = $message;

            curlUtil::post($postUrl, $data);
        }
        // 发送给邀请关注的人
        if($useridList) {
            $data = DataEnum::$msgData;
            $data['userList'] = $useridList;
            $message = '◉ ' . Session::get("info")["user_name"] . '邀请您关注' . $mission->mission_id . '号任务' . "\n" . $templet;
            $data['data']['content'] = $message;

            curlUtil::post($postUrl, $data);
        }

        return Result::returnResult(Result::SUCCESS);
    }

    /**
     * 显示指定的任务
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function read($id)
    {
        $sessionUserId = Session::get("info")["user_id"];
        // 判断用户是否有权限查看任务详情
        if(!MissionService::isView($sessionUserId, $id)) {
            return Result::returnResult(Result::NO_ACCESS);
        }
        $mission = Mission::get($id);            // 获取任务详情
        // 处理编号为 0 问题
        $mission->parent_mission_id = ($mission->parent_mission_id == 0)? '' : $mission->parent_mission_id;
        $mission->minute_id = ($mission->minute_id == 0)? '' : $mission->minute_id;
        $mission->requirement_id = ($mission->requirement_id == 0)? '' : $mission->requirement_id;
        $mission->problem_id = ($mission->problem_id == 0)? '' : $mission->problem_id;
        // 关联查找用户名和转换状态码成信息
        $mission->reporter_name = $mission->reporter->user_name;
        $mission->assignee;
        // 获取任务关注人列表
        $nameArray = array();
        $idArray = array();
        if($mission->missionInterests) {
            foreach ($mission->missionInterests as $missionInterest) {
                $missionInterest->user_name = $missionInterest->user->user_name;
                unset($missionInterest->user);
            }
        }

        // 获取项目列表
        $projectList = ProjectService::getProjectList();

        // 获取任务状态列表
        $missionStatus = new MissionStatus();
        $statusList = $missionStatus->field('status_id,status_name')->select();

        // 判断当前用户是否是发起人
        $isReporter = ($sessionUserId == $mission->reporter_id)? 1 : 0;

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


        $mission->labelList = LabelService::getMissionLabelList($mission->mission_id);          // 获取任务标签列表
        $labelList = LabelService::getLabelList();          // 获取标签列表

        $data = [
            'missionDetail' => $mission,
            'projectList' => $projectList,
            'statusList' => $statusList,
            'labelList' => $labelList,
            'isReporter' => $isReporter
        ];

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
     * @param $id
     * @return array
     * @throws \think\exception\DbException
     */
    public function update($id)
    {
        $mission = Mission::get($id);
        $sessionUserId = Session::get("info")["user_id"];
        // 更新任务信息
        $fields = input('put.');
        $fields['parent_mission_id'] = input('put.is_root')? -1 : input('parent_mission_id');
        $mission->allowField(true)->save($fields);

        // 处理关注人列表
        $newUserids = [];         // 新邀请关注人的钉钉 userid
        $oldUserids = [];         // 已关注人的钉钉 userid
        if(input('put.invite_follow')) {
            $userIds = explode(',', input('put.invite_follow'));
            // 获取当前的关注人列表
            $missionInterest = new MissionInterest();
            $currentUserIds = $missionInterest->where('mission_id', $id)->column('user_id');
            $newUserIds = array_diff($userIds, $currentUserIds);         // 新邀请关注的人
            $oldUserIds = array_intersect($userIds, $currentUserIds);           // 已关注的人
            $cancelUserIds = array_diff($currentUserIds, $userIds);        // 取消关注的人

            foreach ($newUserIds as $userId) {
                if($userId == $mission->reporter_id || $userId == $mission->assignee_id) {          // 过滤发起人和处理人
                    continue;
                }
                $missionInterest = new MissionInterest();
                $missionInterest->mission_id = $id;
                $missionInterest->user_id = $userId;
                $missionInterest->save();

                $user = User::getByUserId($userId);
                if($userId != $sessionUserId && $user->dd_userid != '' && $user->dd_open == 1) {          // 过滤当前用户
                    array_push($newUserids, $user->dd_userid);
                }
            }
            foreach ($oldUserIds as $userId) {
                $user = User::getByUserId($userId);
                if($userId != $sessionUserId && $user->dd_userid != '' && $user->dd_open == 1) {
                    array_push($oldUserids, $user->dd_userid);
                }
            }
            foreach ($cancelUserIds as $userId) {
                $missionInterest = MissionInterest::get(['mission_id' => $id, 'user_id' => $userId]);
                if($missionInterest) {
                    $missionInterest->delete();
                }
            }
        }

        // 插入任务处理记录
        $attachmentList = '';           // 附件清单字符串
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
                $attachmentArray = array();
                foreach ($attachmentIds as $attachmentId) {
                    $attachment = Attachment::get($attachmentId);
                    $attachment->attachment_type = 'mission';
                    $attachment->related_id = $missionProcess->process_id;
                    $attachment->save();

                    array_push($attachmentArray, $attachment->source_name);
                }
                $attachmentList = implode('，', $attachmentArray);
            }
        }

        // 处理任务标签
        if(input('put.label_list')) {
            $labelList = explode('；', input('put.label_list'));
            foreach ($labelList as $label) {
                $label = Label::get(['label_name' => $label]);
                if(!$label) {
                    $label = new Label();
                    $label->label_name = $label;
                    $label->save();
                }
                $missionLabel = MissionLabel::get(['mission_id' => $mission->mission_id, 'label_id' => $label->label_id]);
                if(!$missionLabel) {
                    $missionLabel = new MissionLabel();
                    $missionLabel->mission_id = $mission->mission_id;
                    $missionLabel->label_id = $label->label_id;
                    $missionLabel->save();
                }
            }
        }

        // 发送钉钉消息
        $status_name = MissionStatus::get($mission->status)->status_name;
        $postUrl = 'http://www.bjzzdr.top/us_service/public/other/ding_ding_c/sendMessage';
        $url = 'http://192.168.0.249/office_automation/public/static/layuimini/?missionId=' . $mission->mission_id;
        $data = DataEnum::$msgData;
        if(input('put.process_note') != '' || input('put.attachment_list') != '') {
            $templet = '▪ 标题：' . $mission->mission_title . "\n" . '▪ 处理后状态：' . $status_name;
            if(input('put.process_note') != '') {
                $templet .= "\n" . '▪ 处理意见：' . input('put.process_note');
            }
            if($attachmentList != '') {
                $templet .= "\n" . '▪ 附件清单：' . $attachmentList;
            }
            $templet .= "\n" . '▪ 链接：' . $url;

            // 发送给处理人
            if($sessionUserId != $mission->assignee_id && $mission->assignee->dd_userid != '') {
                $data['userList'] = $mission->assignee->dd_userid;
                $message = '◉ ' . Session::get("info")["user_name"] . '处理了' . $mission->mission_id . '号任务' . "\n" . $templet;
                $data['data']['content'] = $message;

                curlUtil::post($postUrl, $data);
            }
            // 发送给发起人
            if($sessionUserId != $mission->reporter_id && $mission->reporter->dd_userid != '') {
                $data['userList'] = $mission->reporter->dd_userid;
                $message = '◉ ' . '您发起的' . $mission->mission_id . '号任务' . '正在被' . Session::get("info")["user_name"] . '处理' . "\n" . $templet;
                $data['data']['content'] = $message;

                curlUtil::post($postUrl, $data);
            }
            // 发送给已关注的人
            if(!empty($oldUserids)) {
                $data['userList'] = implode(',', $oldUserids);
                $message = '◉ ' . '您关注的' . $mission->mission_id . '号任务正在被' . Session::get("info")["user_name"] . '处理' . "\n" . $templet;
                $data['data']['content'] = $message;

                curlUtil::post($postUrl, $data);
            }
        }

        // 发送给新邀请关注的人
        if(!empty($newUserids)) {
            $data['userList'] = implode(',', $newUserids);
            $templet = '▪ 标题：' . $mission->mission_title . "\n" . '▪ 描述：' . $mission->description . "\n" . "▪ 截止日期：" . $mission->finish_date;
            if($attachmentList != '') {
                $templet .= "\n" . '▪ 附件清单：' . $attachmentList;
            }
            $templet .= "\n" . '▪ 链接：' . $url;
            $message = '◉ ' . Session::get("info")["user_name"] . '邀请您关注' . $mission->mission_id . '号任务' . "\n" . $templet;
            $data['data']['content'] = $message;

            curlUtil::post($postUrl, $data);
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

    /**
     * 修改任务优先级
     * @param $id
     * @return array
     * @throws \think\exception\DbException
     */
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

    /**
     * 将任务加进浏览记录
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function recordMissionView($missionId) {
        $sessionUserId = Session::get("info")["user_id"];
        $dateTime = date('Y-m-d H:i:s',time());
        if(!$missionId) {           // 如果缺少必需参数
            return Result::returnResult(Result::LACK_REQUIRED_PARAM);
        }
        if(!Mission::get($missionId)) {
            return Result::returnResult(Result::OBJECT_NOT_EXIST);
        }

        $missionView = MissionView::get(['user_id' => $sessionUserId, 'mission_id' => $missionId]);
        if($missionView) {          // 如果有相同任务浏览记录
            $missionView->update_time = $dateTime;
            $missionView->save();
        } else {
            $missionView = new MissionView();
            $count =$missionView->where('user_id', $sessionUserId)->count();
            if($count >= 15) {          // 修改最久的记录
                $missionView = new MissionView();
                $oldView = $missionView->where('user_id', $sessionUserId)->order('update_time asc')->find();
                $oldView->mission_id = $missionId;
                $oldView->save();
            } else {            // 新增记录
                $missionView->user_id = $sessionUserId;
                $missionView->mission_id = $missionId;
                $missionView->save();
            }
        }

        return Result::returnResult(Result::SUCCESS);
    }

    /**
     * 获取最近浏览的 15 条任务
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMissionView() {
        $sessionUserId = Session::get("info")["user_id"];
        $missionView = new MissionView();
        $viewList = $missionView->where('user_id', $sessionUserId)->field('mission_id')->order('update_time desc')->select();

        foreach ($viewList as $view) {
            $view->mission;
        }

        return Result::returnResult(Result::SUCCESS, $viewList, count($viewList));
    }

    /**
     * 获取关注的根任务列表
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function treeIndex($page = 1, $limit = 10)
    {
        $mission = new Mission();
        $sessionUserId = Session::get("info")["user_id"];
        // 如果传入关键词、项目代号、标签、根任务
        if(input('get.project_id') != '') {            // 关联项目
            $mission->where('project_id',input('get.project_id'));
        }
        if(input('get.label') != '') {          // 标签
            $label = input('get.label');
            $mission->alias('m')->join('oa_mission_label ml',"m.mission_id = ml.mission_id")->join('oa_label l',"l.label_id = ml.label_id and label_name like '%$label%'");
        }
        $count = $mission->where('parent_mission_id', -1)->alias('m')->join('oa_mission_interest mi',"mi.user_id= '$sessionUserId' and m.mission_id = mi.mission_id")->group('m.mission_id')->count();
        // 如果传入关键词、项目代号、标签、根任务 TODO
        if(input('get.project_id') != '') {            // 关联项目
            $mission->where('project_id',input('get.project_id'));
        }
        if(input('get.label') != '') {          // 标签
            $label = input('get.label');
            $mission->alias('m')->join('oa_mission_label ml',"m.mission_id = ml.mission_id")->join('oa_label l',"l.label_id = ml.label_id and label_name like '%$label%'");
        }
        $mission->where('parent_mission_id', -1);
        if(input('get.field') != '') {          // 排序
            $mission->order(input('get.field') . ' ' . input('get.order'));
        } else {
            $mission->order('mission_id desc');
        }
        $missions = $mission->alias('m')->join('oa_mission_interest mi',"mi.user_id= '$sessionUserId' and m.mission_id = mi.mission_id")->group('m.mission_id')->page("$page, $limit")->field('m.mission_id,mission_title,assignee_id,status,project_id,label')->select();

        // 处理结果集
        foreach ($missions as $one) {
            $one->assignee_name = $one->assignee->user_name;            // 关联处理人
            $one->status = $one->missionStatus->status_name;          // 转换状态码成信息
            $one->project_id = $one->project->project_code;            // 关联项目
            unset($one->assignee_id, $one->assignee, $one->label, $one->missionStatus, $one->project);

            // 获取标签列表
            $one->labelList = LabelService::getMissionLabelList($one->mission_id);
        }

        return Result::returnResult(Result::SUCCESS, $missions, $count);
    }

    /**
     * 获取任务树时间戳列表
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
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

    /**
     * 盖时间戳
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
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

    /**
     * 获取任务树进展详情
     * @param int $id
     * @param string $date
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
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

        $changeList = empty($changeList)? [] : collection($changeList)->visible(['mission_id','mission_title','assignee_change','status_change','date_change'])->toArray();
        $addList = empty($addList)? [] : collection($addList)->visible(['mission_id','mission_title','type'])->toArray();
        $deleteList = empty($deleteList)? [] : collection($deleteList)->visible(['mission_id','mission_title','type'])->toArray();
        $data = [
            'changeList' => $changeList,
            'addList' => $addList,
            'deleteList' => $deleteList
        ];
        return Result::returnResult(Result::SUCCESS, $data);
    }

    /**
     * 获取任务树详情
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function missionTreeDetail($id) {
        // 获取任务树
        $missionService = new MissionService();
        $missionTree = $missionService->getMissionTree($id);
        $sessionUserId = Session::get("info")["user_id"];
        // 判断是否是管理员
        $user = User::get(['user_id' => $sessionUserId]);
        $data = [
            'missionTree' => $missionTree,
            'isSuper' => $user->super
        ];

        return Result::returnResult(Result::SUCCESS, $data);
    }

    /**
     * 删除任务树任务
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function deleteTreeMission($id) {
        // 获取子任务列表
        $mission = Mission::get($id);

        if($mission->parent_mission_id == -1) {
            return Result::returnResult(Result::FORBID_DELETE_ROOT);
        } else {
            $mission->parent_mission_id = 0;
            $mission->save();

            return Result::returnResult(Result::SUCCESS);
        }
    }

    /**
     * 添加任务树任务
     * @param $id
     * @return array
     * @throws \think\exception\DbException
     */
    public function addTreeMission($id)
    {
        $mission = Mission::get($id);
        $type = input('post.type');
        $missionId = input('post.mission_id');
        $sessionUserId = Session::get("info")["user_id"];
        // 位置
        if(input('post.position') == 'sibling') {
            $id = $mission->parent_mission_id;
        }
        // 方式
        if($type == 'new') {            // 新增任务
            $minute_id = input('post.minute_id')? input('post.minute_id') : 0;
            $infoArray = array_merge($_POST, [
                'reporter_id' => $sessionUserId,
                'minute_id' => $minute_id,
                'parent_mission_id' => $id,
                'create_time' => date('Y-m-d H:i:s', time())
            ]);
            $mission = new Mission($infoArray);
            $mission->allowField(true)->save();
            $this->recordMissionView($mission->mission_id);         // 加进任务浏览记录
        } else if($type == 'exist') {           // 已存在任务
            $existMission = Mission::get($missionId);
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
                'reporter_id' => $sessionUserId,
                'assignee_id' => $minute->host_id,
                'status' => 2,         // 任务状态：已完成 TODO
                'start_date' => date("Y-m-d", time()),
                'finish_date' => $minute->minute_date,
                'description' => $minute->record,
                'minute_id' => input('post.minute_id'),
                'parent_mission_id' => $id
            ]);
        }
        // 继承关注人
        if($type == 'new' || $type == 'minute') {
            $interestList = MissionService::getInterestList($id);
            foreach ($interestList as $interest) {
                $missionInterest = new MissionInterest();
                $missionInterest->mission_id = $mission->mission_id;
                $missionInterest->user_id = $interest->user_id;
                $missionInterest->save();
            }
        } else {
            $interestList = MissionService::getInterestList($id);           // 继承的关注人列表
            $existInterestList = MissionService::getInterestList($missionId);           // 已关注人列表
            foreach ($interestList as $interest) {
                $result = true;
                foreach ($existInterestList as $existInterest) {
                    if($existInterest->user_id == $interest->user_id) {
                        $result = false;
                    }
                }
                if($result) {
                    $missionInterest = new MissionInterest();
                    $missionInterest->mission_id = $missionId;
                    $missionInterest->user_id = $interest->user_id;
                    $missionInterest->save();
                }
            }
        }
        // 发送钉钉消息
        $data = DataEnum::$msgData;
        $postUrl = 'http://www.bjzzdr.top/us_service/public/other/ding_ding_c/sendMessage';
        if($type == 'new') {
            $url = 'http://192.168.0.249/office_automation/public/static/layuimini/?missionId=' . $mission->mission_id;
            if($sessionUserId != input('post.assignee_id') && $mission->assignee->dd_userid != '') {
                $data['userList'] = $mission->assignee->dd_userid;
                $templet = '▪ 标题：' . $mission->mission_title . "\n" . '▪ 描述：' . $mission->description . "\n" . "▪ 截止日期：" . $mission->finish_date;
                $templet .= "\n" . '▪ 链接：' . $url;
                $message = '◉ 您有新的任务(#' . $mission->mission_id . ')待处理' . "\n" . $templet;

                $data['data']['content'] = $message;
                curlUtil::post($postUrl, $data);
            }
        }

        return Result::returnResult(Result::SUCCESS);
    }

    /**
     * 关注任务树所有任务
     * @return array
     * @throws \think\exception\DbException
     */
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

                        // 发送钉钉通知 TODO
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

    /**
     * 获取任务树任务详情
     * @param $id
     * @return array
     * @throws \think\exception\DbException
     */
    public function getMissionDetail($id) {

        $mission = Mission::get($id);
        $userList = [];
        if($mission->missionInterests) {
            foreach ($mission->missionInterests as $missionInterest) {
                array_push($userList, $missionInterest->user->user_name);
            }
        }
        $data = [
            'interestList' => implode('，', $userList),
            'parentMissiond' => $mission->parent_mission_id
        ];

        return Result::returnResult(Result::SUCCESS, $data);
    }

    // 获取任务的根任务
    public function getRootMissionId($missionId)
    {
        while (true) {
            $mission = Mission::get($missionId);
            if($mission->parent_mission_id == -1) {
                return Result::returnResult(Result::SUCCESS, $mission->mission_id);
            } else if($mission->parent_mission_id == 0){
                return Result::returnResult(Result::HAVE_NO_ROOT);;
            } else {
                $missionId = $mission->parent_mission_id;
            }
        }
    }

    /**
     * 获取工作日历任务
     * @param $type 我头上的、我发起的、我关注的任务
     * @param int $offset 月偏移量
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCalendarMission($type, $offset = 0)
    {
        $mission = new Mission();
        $userId = Session::get("info")["user_id"];
        $dateArray = dateUtil::getMonthFirstAndLast($offset);           // 获取月份第一天和最后一天

        // 我头上的、我发起的、我关注的
        if(input('get.type') == 'assign') {
            $mission->where('assignee_id', $userId);
        } else if(input('get.type') == 'report') {
            $mission->where('reporter_id', $userId)->where('assignee_id', 'neq', $userId);
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

    /**
     * 更新小任务到 OA
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getLittleMission() {
        header('Access-Control-Allow-Origin: *');           // 允许所有域名访问
        // 获取上一次保存的最后一个任务号
        $data = Data::get(['data_name' => 'little_mission']);
        $current_id = $data->data_value;

        $littleMission = new LittleMission();
        $littleMissionList = $littleMission->where('mission_id', '>', $current_id)->select();
        foreach ($littleMissionList as $key => $littleMission) {
            $mission = new Mission();
            $mission->data([
                'mission_title' => $littleMission->mission_title,
                'reporter_id' => $littleMission->reporter_id,
                'assignee_id' => $littleMission->assignee_id,
                'description' => $littleMission->description,
                'status' => 0,
                'start_date' => $littleMission->start_date,
                'finish_date' => $littleMission->finish_date,
                'create_time' => $littleMission->create_time
            ]);
            $mission->save();
            $littleMission->oa_mission_id = $mission->mission_id;
            $littleMission->save();
            if($key == count($littleMissionList) - 1) {         // 最后一个
                $data->data_value = $littleMission->mission_id;
                $data->save();
            }
        }

        return Result::returnResult(Result::SUCCESS);
    }
}
