<?php
/**
 * Created by PhpStorm.
 * User: Link
 * Date: 2020/9/16
 * Time: 14:25
 */

namespace app\index\controller;

use app\common\model\Minute;
use app\common\Result;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Session;

/**
 * 会议纪要模块功能
 * Class MeetingC
 * @package app\index\controller
 */
class MeetingC
{

    /**
     * 实现根据会议类型、项目代号和会议名字模糊查询
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws \think\Exception
     */
    public function getAllMyMeet($page = 1,$limit = 10,$projectCode = "",$minuteType = 0,$keyword = ""){
        $userId = Session::get("info")["user_id"];
        //查询与自己相对应的会议id号
        $listMeetId = Db::table('oa_minute_attend')
            ->where("user_id","=",$userId)
            ->column('minute_id');
        //查询对应的会议信息
        $minutes = new Minute();
        $minutes -> where("minute_id","in",$listMeetId);
        //模糊查询条件
        if($keyword != ""){
             $minutes -> where("minute_theme","like","%$keyword%");
        }else{
            //对项目代号进行查询
            if($projectCode != "" && $projectCode != "0"){
                $minutes -> where("project",$projectCode);
            }
            //对会议类型进行查询
            if($minuteType != 0){
                $minutes -> where("minute_type",$minuteType);
            }
        }
        //查询共有多少条符合条件的数据(分页需要使用到)
        $count =  $minutes ->count();
        $minutes -> where("minute_id","in",$listMeetId);
        if($keyword != ""){
            $minutes -> where("minute_theme","like","%$keyword%");
        }else{
            //对项目代号进行查询
            if($projectCode != "" && $projectCode != "0"){
                $minutes -> where("project",$projectCode);
            }
            //对会议类型进行查询
            if($minuteType != 0){
                $minutes -> where("minute_type",$minuteType);
            }
        }
        $listMineMeet = $minutes -> field("minute_id,minute_theme,host_id,minute_date,minute_time,project,minute_type,review_status,project_stage")
                                 -> page($page,$limit)
                                 -> select();
        foreach ($listMineMeet as $minute){
            $users = "";                                                        //所有应到的员工
            $minute -> minute_host = $minute -> user -> user_name;              //会议主持人名字
            $minute -> minute_type = $minute -> minuteType -> type_name;        //会议类型名字
            $minute -> review_name = $minute -> minuteReview -> review_name;    //会议评审状态
            $minute -> stage_name = $minute -> projectStage -> stage_name;      //项目阶段
            $listAttend = $minute-> minuteAttends;
            foreach ($listAttend as $attend){
                $attend -> user;                                                //应到会议人员和用户信息表一对一关联
                $userName =  $attend -> user -> user_name;
                $users = $users . $userName . ";";
            }
            $minute -> attends = $users;
        }
        return Result::returnResult(Result::SUCCESS, $listMineMeet,$count);
    }


    /**
     * 查询所有项目代号、会议的类型和我应到的会议
     * @return array
     */
    public function selectInfo(){
        try {
            //查询项目代买类型
            $listDepartment = Db::table('oa_project')
                ->field("project_id,project_code")
                ->select();
            //查询会议类型
            $listMeet = Db::table('oa_minute_type')
                ->field("type_id,type_name")
                ->select();
            $resultArray = [
                "projectType" => $listDepartment,   //项目类型
                "minuteType" => $listMeet            //会议类型
            ];
            return Result::returnResult(Result::SUCCESS,$resultArray);
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
    }

}