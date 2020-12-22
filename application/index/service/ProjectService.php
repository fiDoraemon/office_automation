<?php
/**
 * Created by PhpStorm.
 * User: TZX
 * Date: 2020/9/11
 * Time: 15:03
 */

namespace app\index\service;

use app\index\model\Project;

class ProjectService
{
    /*
     * 获取项目列表
     */
    public static function getProjectList() {
        $project = new Project();
        $projectList = $project->field('project_id, project_code')->select();

        return $projectList;
    }

    /**
     * 获取项目代号
     * @param $projectId
     * @return mixed
     * @throws \think\exception\DbException
     */
    public static function getProjectCode($projectId) {
        $project = Project::get($projectId);

        return $project->project_code;
    }
}