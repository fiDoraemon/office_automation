<?php

namespace app\index\controller;

use app\common\Result;
use app\index\model\Project;
use think\Controller;
use think\Request;

class ProjectC extends Controller
{
    /**
     * 显示项目列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $project = new Project();
        $data = $project->field('project_id, project_code')->select();

        return Result::returnResult(Result::SUCCESS, $data, count($data));
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        // 获取项目列表
        $projectList = ProjectService::index();
        // 获取任务标签列表
    }

    /**
     * 保存新建的资源
     *
     * @param  \think\Request  $request
     * @return \think\Response
     */
    public function save(Request $request)
    {
        //
    }

    /**
     * 显示指定的资源
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        //
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
     * 保存更新的资源
     *
     * @param  \think\Request  $request
     * @param  int  $id
     * @return \think\Response
     */
    public function update(Request $request, $id)
    {
        //
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
}
