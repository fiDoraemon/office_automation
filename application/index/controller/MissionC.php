<?php

namespace app\index\controller;

use app\index\common\DataEnum;
use app\index\model\Mission;
use app\index\model\MissionProcess;
use app\index\service\ProjectService;
use app\common\Result;
use think\Controller;
use think\Request;
use think\Session;

class MissionC extends Controller
{
    /**
     * 显示任务列表
     *
     * @return \think\Response
     */
    public function index($page = 1, $limit = 10, $keyword = '')
    {
        $mission = new Mission();
        // 如果传入关键词、项目代号、标签
        if($keyword != '') {
            $mission->where('mission_title','like',"%$keyword%");
        }
        if(input('get.related_project') != '') {
            $mission->where('related_project',input('get.related_project'));
        }
        if(input('get.label') != '') {
            $mission->where('label',input('get.label'));
        }
        if(input('get.field') != '') {
            $mission->order(input('get.field') . ' ' . input('get.order'));
        }
        $count = $mission->count();
        // 如果传入关键词、项目代号、标签 TODO
        if($keyword != '') {
            $mission->where('mission_title','like',"%$keyword%");
        }
        if(input('get.related_project') != '') {
            $mission->where('related_project',input('get.related_project'));
        }
        if(input('get.label') != '') {
            $mission->where('label',input('get.label'));
        }
        if(input('get.field') != '') {
            $mission->order(input('get.field') . ' ' . input('get.order'));
        }
        $missions = $mission->field('mission_id,mission_title,reporter_id,status,priority,label,start_date,finish_date')->page("$page, $limit")->select();

        // 处理结果集
        Session::set('user_type', 'reporter_id');
        foreach ($missions as $one) {
            $one->reporter_name = $one->user->user_name;            // 关联查找用户名
            $one->status = DataEnum::$missionStatus[$one->status];          // 转换状态码成信息
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
        // 获取项目列表

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
     * 显示指定的任务
     *
     * @param  int  $id
     * @return \think\Response
     */
    public function read($id)
    {
        // 获取任务详情
        $mission = Mission::get($id);
        // 关联查找用户名和转换状态码成信息
        Session::set('user_type', 'reporter_id');
        $mission->reporter_id = $mission->user->user_name;
        Session::set('user_type', 'assignee_id');
        $mission->assignee_name = $mission->user->user_name;
        $mission->status = DataEnum::$missionStatus[$mission->status];

        // 获取项目列表
        $projectList = ProjectService::index();

        return Result::returnResult(Result::SUCCESS, ['missionDetail' => $mission, 'projectList' => $projectList]);
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
