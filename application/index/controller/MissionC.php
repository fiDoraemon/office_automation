<?php

namespace app\index\controller;

use app\index\model\Mission;
use app\index\model\MissionProcess;
use app\index\service\ProjectService;
use app\common\Result;
use think\Controller;
use think\Request;

class MissionC extends Controller
{
    /**
     * 显示资源列表
     *
     * @return \think\Response
     */
    public function index()
    {
        $mission = new Mission();
        $missions = $mission->select();
        $result = Result::returnResult(Result::SUCCESS, $missions, count($missions));

        return $result;
    }

    /**
     * 显示创建资源表单页.
     *
     * @return \think\Response
     */
    public function create()
    {
        // 获取项目列表
        $projectService = new ProjectService();
        $projectList = $projectService->index();

        return Result::returnResult(Result::SUCCESS, ['projectList' => $projectList], count($projectList));
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
            'interested_list' => input('post.invite_follow_ids'),
            'create_time' => date('Y-m-d H:i:s', time())
        ]);
        $mission = new Mission($infoArray);
        $mission->allowField(true)->save();

        return Result::returnResult(Result::SUCCESS);
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
