<?php
/**
 * Created by PhpStorm.
 * User: TZX
 * Date: 2020/10/14
 * Time: 10:18
 */

namespace app\index\controller;

use app\common\model\FileManageSystemAttachmentInfo;
use app\common\model\MinuteInfo;
use app\common\model\MissionHistory;
use app\common\model\ReferenceProjectInfo;
use app\common\model\UserInfo;
use app\common\util\EncryptionUtil;
use app\index\model\Attachment;
use app\index\model\Department;
use app\index\model\Minute;
use app\index\model\Mission;
use app\index\model\MissionInterest;
use app\index\model\MissionProcess;
use app\index\model\MissionView;
use app\index\model\Project;
use app\index\model\ProjectManager;
use app\index\model\User;

/**
 * 转移旧OA数据库表控制器
 * Class MoveTableData
 * @package app\index\controller
 */
class MoveTableData
{
    // 部门名称过滤
    public function departmentFilter($name) {
        if(stristr($name,"质量")) {
            return '质量部';
        } else if(stristr($name,"总经")) {
            return '总经办';
        } else if(stristr($name,"研发") || stristr($name,"仪器")) {
            return '仪器研发部';
        } else if(stristr($name,"行政")) {
            return '行政部';
        } else if(stristr($name,"市场") || stristr($name,"营销")) {
            return '市场营销部';
        } else {
            return $name;
        }
    }

    // 转移用户表数据
    public function moveUserData()
    {
        $userInfo = new UserInfo();
        $objects = $userInfo->limit(10)->select();
//        MissionInfo::chunk(100, function ($objects) {});
        $date = date('Y-m-d H:i:s',time());
        foreach ($objects as $object) {
            // 处理值
            $object->email = $object->email ? $object->email : '';
            $object->userid = $object->userid ? $object->userid : '';
            $object->open = ($object->open == 1) ? 1 : 0;
            $object->SU = ($object->SU == 1) ? 1 : 0;
            $object->obsolete = ($object->obsolete == 1) ? 0 : 1;

            // 处理部门
            $name = $this->departmentFilter($object->department);
            $department = Department::getByDepartmentName($name);
            if (!$department) {
                $department = new Department();
                $department->department_name = $object->department;
                $department->save();
            }
            $department_id = $department->department_id;

            $password = EncryptionUtil::Md5Encryption('123', $object->User_ID);         // 初始密码为 123
            $user = User::get(['user_id' => $object->User_ID]);
            if(!$user) {
                $user->data([
                    'user_id' => $object->User_ID,
                    'user_name' => $object->Name,
                    'password' => $password,
                    'department_id' => $department_id,
                    'email' => $object->email,
                    'user_status' => $object->obsolete,
                    'userid' => $object->userid,
                    'super' => $object->SU,
                    'dd_open' => $object->open,
                    'create_time' => $date
                ]);
                $user->save();
            }

            // 处理用户浏览的任务
            $missionIdList = explode(';', $object->recent_mission);
            foreach ($missionIdList as $missionId) {
                if ($missionId == '') {         // TODO
                    continue;
                }
                $missionView = MissionView::get(['user_id' => $user->user_id, 'mission_id' => $missionId]);
                if(!$missionView) {
                    $missionView = new MissionView();
                    $missionView->user_id = $user->user_id;
                    $missionView->mission_id = $user->mission_id;
                    $missionView->save();
                }
            }
        }
        return '用户表数据转移完成';
    }

    // 转移项目表数据
    public function moveProjectData()
    {
        $referenceProjectInfo = new ReferenceProjectInfo();
        $objects = $referenceProjectInfo->limit(10)->select();
//        MissionInfo::chunk(100, function ($objects) {});
        $date = date('Y-m-d H:i:s',time());
        foreach ($objects as $object) {
            $project = Project::get(['project_code' => $object->PROJECT_CODE]);
            if(!$project) {
                $project = new Project();
                $project->project_code = $object->PROJECT_CODE;
                $project->project_name = $object->PROJECT_NAME;
                $project->description = $object->SUMMARY;
                $project->create_time = $date;
                $project->save();
            }

            // 处理管理者
            $userIds = explode(';', $object->ADMINISTER);
            foreach ($userIds as $userId) {
                if ($userId == '' || !User::get(['user_id', $userId])) {
                    continue;
                }
                $projectManager = ProjectManager::get(['proeject_id' => $project->proeject_id, 'manager_id' => $userId]);
                if(!$projectManager) {
                    $projectManager->proeject_id = $project->proeject_id;
                    $projectManager->manager_id = $userId;
                    $projectManager->save();
                }
            }
        }
        return '项目表数据转移完成';
    }

    // 转移任务表数据
    public function moveMissionData()
    {
        $missionInfo = new MissionInfo();
        $objects = $missionInfo->limit(10)->select();
//        MissionInfo::chunk(100, function ($objects) {});
        $date = date('Y-m-d H:i:s',time());
        foreach ($objects as $object) {
            // 处理值
            $object->standard = $object->standard ? $object->standard : '';
            $object->label = $object->label ? $object->label : '';
            $object->related_requirement = $object->related_requirement? $object->related_requirement : 0;
            $object->related_problem = $object->related_problem? $object->related_problem : 0;
            // 处理任务状态
            if ($object->status == '未开始') {
                $object->status = 0;
            } else if ($object->status == '处理中') {
                $object->status = 1;
            } else if ($object->status == '已完成') {
                $object->status = 2;
            } else {
                $object->status = 3;
            }
            // 处理项目控制
            $object->under_project_control = ($object->under_project_control == 'Y') ? 1 : 0;
            // 处理项目代号
            $project = Project::get(['projec_code' => $object->related_project]);
            $object->related_project = $project? $project->project_id : 0;
            // 处理开始日期
            $object->start_date = $object->start_date? $object->start_date : $object->due_date;
            $object->parent_id = ($object->is_root_mission == 'Y') ? -1 : $object->parent_id;

            $curentMission = Mission::get($object->ID);
            if (!$curentMission) {
                $curentMission = new Mission();
                $parent_mission_id = ($object->is_root_mission == 'Y') ? -1 : 0;
            } else {
                $parent_mission_id = $curentMission->parent_mission_id;
            }
            $curentMission->data([
                'mission_id' => $object->ID,
                'mission_title' => $object->title,
                'reporter_id' => $object->reporter_ID,
                'assignee_id' => $object->assignee_ID,
                'description' => $object->description,
                'finish_standard' => $object->standard,
                'status' => $object->status,
                'priority' => $object->priority,
                'label' => $object->label,
                'start_date' => $object->start_date,
                'finish_date' => $object->due_date,
                'project_id' => $object->related_project,
                'minute_id' => $object->parent_id,
                'parent_mission_id' => $parent_mission_id,
                'requirement_id' => $object->related_requirement,
                'problem_id' => $object->related_problem,
                'project_control' => $object->under_project_control,
                'create_time' => $object->due_date
            ]);
            $curentMission->save();

            // 处理关注人
            $interestList = explode(';', $object->interested_list);
            foreach ($interestList as $interest) {
                if ($interest == '' || !User::get(['user_id', $interest])) {
                    continue;
                }
                $missionInterest = MissionInterest::get(['mission_id' => $curentMission->mission_id, 'user_id' => $interest]);
                if (!$missionInterest) {
                    $missionInterest = new MissionInterest();
                    $missionInterest->mission_id = $curentMission->mission_id;
                    $missionInterest->user_id = $interest;
                    $missionInterest->save();
                }
            }
            // 处理任务树
            $childrenList = explode(';', $object->children_mission_id);
            foreach ($childrenList as $children) {
                if ($children == '') {
                    continue;
                }
                $childMission = Mission::get($children);
                if (!$childMission) {
                    $childMission = new Mission();
                }
                $childMission->mission_id = $children;
                $childMission->parent_mission_id = $curentMission->mission_id;
                $childMission->save();
            }
        }
        return '任务表数据转移完成';
    }

    // 转移任务处理数据
    public function moveMissionHistoryData() {
        $missionHistory = new MissionHistory();
        $objects = $missionHistory->limit(10)->select();
//        MissionInfo::chunk(100, function ($objects) {});
        $date = date('Y-m-d H:i:s',time());
        foreach ($objects as $object) {
            // 处理任务状态
            if ($object->post_status == '未开始') {
                $object->post_status = 0;
            } else if ($object->post_status == '处理中') {
                $object->post_status = 1;
            } else if ($object->post_status == '已完成') {
                $object->post_status = 2;
            } else {
                $object->post_status = 3;
            }
            $missionProcess = new MissionProcess();
            $missionProcess->mission_id = $object->mission_id;
            $missionProcess->handler_id = User::get(['user_id' => $object->handler])? $object->handler : 0;
            $missionProcess->process_note = $object->process_note;
            $missionProcess->post_assignee_id = 0;
            $missionProcess->post_status = $object->post_status;
            $missionProcess->post_finish_date = $object->due_date;
            $missionProcess->process_time = $object->process_date . ' ' . $object->process_time;
            $missionProcess->save();
            // 处理附件列表
            $attachmentList = explode(';', $object->attachment_list);
            foreach ($attachmentList as $attachment) {
                if ($attachment == '') {
                    continue;
                }
                $fileManageSystemAttachmentInfo = FileManageSystemAttachmentInfo::get(['source_name' => $attachment]);
                if ($fileManageSystemAttachmentInfo) {
                    // 插入附件信息
                    $attachment = Attachment::get(['storage_name' => $attachment]);
                    if(!$attachment) {
                        $attachment = new Attachment();
                        $attachment->source_name = $fileManageSystemAttachmentInfo->source_name;
                        $attachment->storage_name = $fileManageSystemAttachmentInfo->storage_name;
                        $attachment->uploader_id = User::get(['user_id' => $fileManageSystemAttachmentInfo->uploader_ID])? $fileManageSystemAttachmentInfo->uploader_ID : 0;;
                        $attachment->attachment_type = 'mission';
                        $attachment->related_id = $missionProcess->process_id;
                        $attachment->file_size = $fileManageSystemAttachmentInfo->file_size;
                        // 保存路径为 月份/保存文件名
                        $attachment->save_path = date("Ym", strtotime($fileManageSystemAttachmentInfo->upload_date)) . '/' . $fileManageSystemAttachmentInfo->storage_name;
                        $attachment->upload_time = $fileManageSystemAttachmentInfo->upload_date;
                    }
                    // 转移真实附件 TODO
                }
            }
        }
        return '任务处理表数据转移完成';
    }

    // 转移任务树表进展详情数据
    // 转移会议表数据
    public function moveMinuteData() {
        MinuteInfo::chunk(100, function ($objects) {
            foreach ($objects as $object) {
                // 处理部门
                $department = Department::getByDepartmentName($object->DEPARTMENT);
                if (!$department) {
                    $department = new Department();
                    $department->department_name = $object->DEPARTMENT;
                    $department->save();
                }
                $department_id = $department->department_id;
                // 处理项目代号

                $minute = new Minute();
                $minute->department_id = $department_id;
                $minute->minute_theme = $object->THEME;
                $minute->host_id = $department_id;
                $minute->place = $department_id;
                $minute->minute_date = $department_id;
                $minute->minute_time = $department_id;
                $minute->project = $department_id;
                $minute->resolution = $department_id;
                $minute->record = $department_id;
                $minute->minute_type = $department_id;
                $minute->review_status = $department_id;
                $minute->project_stage = $department_id;
                $minute->create_time = $department_id;
                $minute->department_id = $department_id;
            }
        });
        return '会议表数据转移完成';
    }

    public function moveAlldata() {
        $this->moveUserData();
        $this->moveProjectData();
        $this->moveMissionData();
        $this->moveMissionHistoryData();
        $this->moveMinuteData();
        return '全部数据转移完成';
    }
}