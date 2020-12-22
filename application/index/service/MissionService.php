<?php
/**
 * Created by PhpStorm.
 * User: TZX
 * Date: 2020/9/21
 * Time: 10:19
 */

namespace app\index\service;

use app\common\util\curlUtil;
use app\index\common\DataEnum;
use app\index\model\Cooperation;
use app\index\model\Mission;
use app\index\model\MissionInterest;
use app\index\model\MissionProcess;
use app\index\model\User;
use think\Session;

/**
 * 任务服务类
 * Class MissionService
 * @package app\index\service
 */
class MissionService
{
    /**
     * 获取任务树
     * @param $id
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMissionTree($id) {
        $rootMission = Mission::where('mission_id', $id)->field('mission_id,mission_title,assignee_id,status,finish_date,parent_mission_id')->find();
        $rootMission->parent_mission_id = -1;
        $rootMission->haveAttachment = MissionService::haveAttachment($rootMission->mission_id)? 1 : 0;

        // 关联处理
        $rootMission->assignee_name = $rootMission->assignee->user_name;
        $rootMission->status_name = $rootMission->missionStatus->status_name;
        unset($rootMission->assignee, $rootMission->missionStatus);

        // 获取子任务树列表
        $missionTree = $this->getChildList($id);
        if($missionTree) {
            array_unshift($missionTree, $rootMission);
        } else {
            $missionTree = [$rootMission];
        }

        return $missionTree;
    }

    /**
     * 获取子任务树
     * @param $id
     * @return array|bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getChildList($id) {
        $missionTree = array();
        $mission = new Mission();

        // 获取子任务列表
        $childMissionList = $mission->where('parent_mission_id', $id)->field('mission_id,mission_title,assignee_id,status,finish_date,parent_mission_id')->select();
        if($childMissionList) {
            foreach ($childMissionList as $childMission) {
                $childMission->haveAttachment = MissionService::haveAttachment($childMission->mission_id)? 1 : 0;
                // 关联处理
                $childMission->assignee_name = $childMission->assignee->user_name;
                $childMission->status_name = $childMission->missionStatus->status_name;
                unset($childMission->assignee, $childMission->missionStatus);

                $result = $this->getChildList($childMission->mission_id);
                if($result == false) {
//                    $childMission->is_parent = 0;
                    array_push($missionTree, $childMission);
                } else {
//                    $childMission->is_parent = 1;
                    array_push($missionTree, $childMission);
                    $missionTree = array_merge($missionTree, $result);
                }
            }
        } else {
            return false;
        }

        return $missionTree;
    }

    /**
     * 判断用户是否有权限查看任务
     * @param $userId
     * @param $missionId
     * @return bool
     * @throws \think\exception\DbException
     */
    public static function isView($userId, $missionId) {
        $mission = Mission::get($missionId);
        // 是否是发起人或者处理人
        if($userId != $mission->reporter_id && $userId != $mission->assignee_id) {
            // 是否是关注人
            if(!MissionInterest::get(['mission_id' => $missionId, 'user_id' => $userId])) {
                // 是否是管理员
                $user = User::get(['user_id' => $userId]);
                if($user->super != 1) {
                    // 是否有合作人关系
                    if(!Cooperation::get(['manager_id' => $mission->reporter_id, 'member_id' => $userId]) &&
                        !Cooperation::get(['manager_id' => $mission->assignee_id, 'member_id' => $userId])) {
                        // 判断是否是工作表的任务
                        if(!Session::get('isViewTavle')) {
                            return false;
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * 判断任务是否有附件
     * @param $missionId
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public static function haveAttachment($missionId) {
        $missionProcess = new MissionProcess();
        $result = $missionProcess->alias('mp')->where('mission_id', $missionId)->join('oa_attachment a',"a.related_id = mp.process_id and a.attachment_type = 'mission'")->count();
        if($result > 0) {
            return true;
        } else {
            return false;
        }
    }

    // 获取任务关注人列表
    public static function getInterestList($missionId) {
        $missionInterest = new MissionInterest();
        $interestList = $missionInterest->where('mission_id', $missionId)->select();

        return $interestList;
    }

    /**
     * 发送钉钉消息
     * @param $missionId
     * @return bool
     * @throws \think\exception\DbException
     */
    public static function sendMessge($missionId) {
        $sessionUserId = Session::get("info")["user_id"];
        $mission = Mission::get($missionId);
        $data = DataEnum::$msgData;
        $postUrl = 'http://www.bjzzdr.top/us_service/public/other/ding_ding_c/sendMessage';
        $url = 'http://192.168.0.249/office_automation/public/static/layuimini/?missionId=' . $mission->mission_id;
        $templet = '▪ 标题：' . $mission->mission_title . "\n" . '▪ 描述：' . $mission->description . "\n" . "▪ 截止日期：" . $mission->finish_date . "\n" . '▪ 链接：' . $url;
        // 发送给处理人
        if ($sessionUserId != $mission->assignee_id && $mission->assignee->dd_userid != '' && $mission->assignee->dd_open == 1) {
            $data['userList'] = $mission->assignee->dd_userid;
            $message = '◉ 您有新的任务(#' . $mission->mission_id . ')待处理' . "\n" . $templet;
            $data['data']['content'] = $message;

            curlUtil::post($postUrl, $data);
        }

        return true;
    }
}