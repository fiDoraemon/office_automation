<?php
/**
 * Created by PhpStorm.
 * User: Link
 * Date: 2020/9/16
 * Time: 14:25
 */

namespace app\index\controller;

use app\common\Result;
use app\index\model\Department;
use app\index\model\Minute;
use app\index\model\User;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Session;

/**
 * 会议纪要模块功能
 * Class MeetingC
 * @package app\index\controller
 * @method getField($string, $true)
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
    public function getAllMyMeet($page = 1,$limit = 10,$projectCode = "",$minuteType = 0,$keyword = "",$isMyLaunch = 0){
        $userId = Session::get("info")["user_id"];
        //查询与自己相对应的会议id号
        $listMeetId = Db::table('oa_minute_attend')
            ->where("user_id","=",$userId)
            ->column('minute_id');
        //查询对应的会议信息
        $minutes = new Minute();
        $minutes -> where("minute_id","in",$listMeetId);
        //是我发起的会议
        if($isMyLaunch == 1){
            $minutes -> where("host_id",$userId);
        }
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
        if($isMyLaunch == 1){
            $minutes -> where("host_id",$userId);
        }
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
    public function getInfo(){
        //查询项目代号类型
        $listCodes = $this -> getProjectCode();
        //查询会议类型
        $listMeet = $this -> getMinutes();
        $resultArray = [
            "projectType" => $listCodes,   //项目类型
            "minuteType" => $listMeet            //会议类型
        ];
        return Result::returnResult(Result::SUCCESS,$resultArray);
    }

    /**
     * @return array
     */
    public function getAddInfo(){
        //部分个人信息
        $user = Session::get("info");
        $userId = $user["user_id"];
        $userName =$user["user_name"];
        $departmentId = $user["department_id"];
        $departmentName = Department::where('department_id',$departmentId)->value('department_name');
        //获取项目代号类型
        $listCodes = $this -> getProjectCode();
        //获取会议类型
        $listMinuteType = $this -> getMinutes();
        $resultArray = [
            "minuteType"        => $listMinuteType,
            "hostId"            => $userId,
            "hostName"          => $userName,
            "departmentName"    => $departmentName,
            "projectType"       => $listCodes,
        ];
        //查询所有人员信息（员工名字、工号、部门id、部门）
        return Result::returnResult(Result::SUCCESS,$resultArray);
    }

    /**
     * 查询所有在职的员工
     * @param int $limit
     * @param int $page
     * @return array
     * @throws \think\Exception
     */
    public function getAllUsers($limit = 10,$page = 1,$keyword = ""){
        //所有在职员工
        $user = new User();
        try {
            $user -> where("user_status", 1);
            if($keyword != ""){   //模糊查询条件
                $Department = new Department();
                $listDepartmentId = $Department -> where("department_name", "like", "%$keyword%")
                      -> column("department_id");
                $user -> where("user_name","like" ,"%$keyword%")
                      -> whereOr('department_id','in',$listDepartmentId);
            }
            $count = $user -> count();  //获取条件符合的总人数
            $user -> where("user_status", 1);
            if($keyword != ""){
                $Department = new Department();
                $listDepartmentId = $Department -> where("department_name", "like", "%$keyword%")
                                                ->column("department_id");
                $user -> where("user_name","like" ,"%$keyword%")
                      -> whereOr('department_id','in',$listDepartmentId);
            }
            $listUser = $user -> field("user_id,user_name,department_id")
                              -> order("department_id")
                              -> page($page,$limit)
                              -> select();
            foreach ($listUser as $u){
                $u -> department;
                $u -> department_name = $u -> department -> department_name;
            }
            return Result::returnResult(Result::SUCCESS,$listUser,$count);
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
    }

    /**
     * 根据某个会议id查询会议的详细信息
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getMinute(){
        $minuteId = $_POST["minuteId"];
        $minute = new Minute();
        $minute -> department();
        $resultMinute = $minute -> where("minute_id", $minuteId)
                -> field("minute_id,department_id,minute_theme,minute_date,minute_time,place,project,host_id,resolution,record,minute_type,review_status,project_stage")
                -> find();
        $resultMinute -> department;
        $resultMinute -> projectStage;
        $resultMinute -> user;
        $attendUsers = $resultMinute -> minuteAttends;
        foreach ($attendUsers as $attend){
            $attend -> user;
        }
        return Result::returnResult(Result::SUCCESS,$resultMinute);
    }


    /**
     * 获取所有会议类型
     * @return false|\PDOStatement|string|\think\Collection
     */
    private function getMinutes(){
        //查询会议类型
        try {
            $listMeet = Db::table('oa_minute_type')
                ->field("type_id,type_name")
                ->select();
            return $listMeet;
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
    }

    /**
     * 查询所有项目代号
     * @return false|\PDOStatement|string|\think\Collection
     */
    private function getProjectCode(){
        try {
            $listProjectCodes = Db::table('oa_project')
                ->field("project_id,project_code")
                ->select();
            return $listProjectCodes;
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
    }

}