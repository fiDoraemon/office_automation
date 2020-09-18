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
    // 获取项目列表
    public static function index() {
        $project = new Project();
        $data = $project->field('project_id, project_code')->select();

        return $data;
    }
}