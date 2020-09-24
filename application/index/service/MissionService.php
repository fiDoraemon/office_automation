<?php
/**
 * Created by PhpStorm.
 * User: TZX
 * Date: 2020/9/21
 * Time: 10:19
 */

namespace app\index\service;

// 任务服务类
use app\index\model\Mission;

class MissionService
{
    // 获取子任务树列表
    public function getMissionTree($id) {
        $missionTree = array();
        $mission = new Mission();
        // 获取子任务列表
        $childMissionList = $mission->where('parent_mission_id', $id)->field('mission_id,mission_title,assignee_id,status,finish_date,parent_mission_id')->select();         // 获取子任务列表
        if($childMissionList) {
            foreach ($childMissionList as $childMission) {
                // 关联处理
                $childMission->assignee_name = $childMission->assignee->user_name;
                $childMission->status = $childMission->missionStatus->status_name;
                unset($childMission->assignee, $childMission->missionStatus);

                $result = $this->getMissionTree($childMission->mission_id);
                if($result == false) {
                    $childMission->is_parent = 0;
                    array_push($missionTree, $childMission);
                } else {
                    $childMission->is_parent = 1;
                    array_push($missionTree, $childMission);
                    $missionTree = array_merge($missionTree, $result);
                }
            }
        } else {
            return false;
        }
        return $missionTree;
    }
}