<?php
/**
 * Created by PhpStorm.
 * User: TZX
 * Date: 2020/9/21
 * Time: 10:19
 */

namespace app\index\service;

use app\index\model\Cooperation;
use app\index\model\Mission;
use app\index\model\User;

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

        if($userId != $mission->reporter_id && $userId != $mission->assignee_id) {
            $user = User::get(['user_id' => $userId]);
            if($user->super != 1) {
                if(!Cooperation::get(['manager_id' => $mission->reporter_id, 'member_id' => $userId]) &&
                    !Cooperation::get(['manager_id' => $mission->assignee_id, 'member_id' => $userId])) {
                    return false;
                }
            }
        }

        return true;
    }
}