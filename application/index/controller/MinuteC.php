<?php
/**
 * Created by PhpStorm.
 * User: Link
 * Date: 2020/9/16
 * Time: 14:25
 */

namespace app\index\controller;

use app\common\Result;
use app\index\model\Attachment;
use app\index\model\Department;
use app\index\model\Minute;
use app\index\model\MinuteAttend;
use app\index\model\MinuteAttendTemp;
use app\index\model\MinuteMission;
use app\index\model\MinuteTemp;
use app\index\model\Mission;
use app\index\model\User;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\Exception;
use think\exception\DbException;
use think\Session;

/**
 * 会议纪要模块功能
 * Class MinuteC
 * @package app\index\controller
 * @method getField($string, $true)
 */
class MinuteC
{

    /**
     * 实现根据会议类型、项目代号和会议名字模糊查询
     * @param int $page 第几页
     * @param int $limit 一页几行数据
     * @param string $projectCode 项目代号
     * @param int $minuteType 会议类型
     * @param string $keyword 模糊查询条件
     * @param int $isMyLaunch 是不是我应到的会议
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
        $count =  $minutes -> count();
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
                                 -> order("minute_id","desc")
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
            "minuteType"  => $listMeet            //会议类型
        ];
        return Result::returnResult(Result::SUCCESS,$resultArray);
    }

    /**
     * 添加会议页面的基本信息
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
     * 查询所有在职的员工 （耦合度较大，后期可考虑解耦）
     * @param int $limit
     * @param int $page
     * @param int $type  0代表查询所有在职员工（发起会议），1代表查询应改到会但未经到会员工（选择实到人员），2代表新增到会员工（需要把已经需要到会的员工排除掉）
     * @param string $keyword 模糊查询条件
     * @param string $minuteId 会议id
     * @return array
     * @throws \think\Exception
     */
    public function getAllUsers($limit = 10,$page = 1,$keyword = "",$minuteId = "",$type = 0){
        //所有在职员工
        $user = new User();
        try {
            $user -> where("user_status", 1);
            //判断查询员工类型
            if($minuteId != ""){
                $attendUsers = new MinuteAttend;
                $attendUsers -> where("minute_id",$minuteId);
                if($type == 1){
                    //查询哪些人员是需要参加会议的,显示应到但是未到的人员
                    //1.先查询某个会议中那些人是需要到的
                    $userList = $attendUsers -> where("status",0) //2.排除掉已经到会的人员
                                             -> column("user_id");
                    $user -> where("user_id", "in",$userList);
                }elseif ($type == 2){
                    //某个会议没有在应参加会议名单里面的员工
                    $userList = $attendUsers -> column("user_id");
                    $user -> where("user_id", "not in",$userList);
                }
            }
            if($keyword != ""){   //模糊查询条件
                $Department = new Department();
                $listDepartmentId = $Department -> where("department_name", "like", "%$keyword%")
                      -> column("department_id");
                $user -> where("user_name","like" ,"%$keyword%")
                      -> whereOr('department_id','in',$listDepartmentId);
            }
            $count = $user -> count();  //获取条件符合的总人数

            $user -> where("user_status", 1);
            if($minuteId != ""){
                $attendUsers = new MinuteAttend;
                $attendUsers -> where("minute_id",$minuteId);
                if($type == 1){
                    //查询哪些人员是需要参加会议的,显示应到但是未到的人员
                    //1.先查询某个会议中那些人是需要到的
                    $userList = $attendUsers -> where("status",0) //2.排除掉已经到会的人员
                    -> column("user_id");
                    $user -> where("user_id", "in",$userList);
                }elseif ($type == 2){
                    //某个会议没有在应参加会议名单里面的员工
                    $userList = $attendUsers -> column("user_id");
                    $user -> where("user_id", "not in",$userList);
                }
            }
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
    public function getMinuteInfo(){
        $minuteId = $_POST["minuteId"];
        $minute = new Minute();
        $minute -> department();
        $resultMinute = $minute -> where("minute_id", $minuteId)
                -> field("minute_id,department_id,minute_theme,minute_date,minute_time,place,project,host_id,resolution,record,minute_type,review_status,project_stage")
                -> find();
        //关联对应发起会议的员工所属部门
        $resultMinute -> department;
        //关联项目
        $resultMinute -> projectStage;
        //关联主持人
        $resultMinute -> user;
        //关联多个应到会人员
        $attendUsers = $resultMinute -> minuteAttends;
        foreach ($attendUsers as $attend){
            $attend -> user;
        }
        //关联多个已经到会人员
        $attendedUsers = $resultMinute -> minuteAttendeds;
        foreach ($attendedUsers as $attended){
            $attended -> user;
        }
        //关联多个附件
        $resultMinute -> attachments;
        //关联一对多会议纪要任务
        $minuteMissions = $resultMinute -> minuteMission;
        foreach ($minuteMissions as $mms){
            //任务和任务负责人一对一关联
            $assignee = $mms -> assignee;   //任务对应的负责人
            $missionStatus = $mms -> missionStatus;
            $process = $mms -> processNew;  //获得任务最近处理信息
            foreach ($process as $pro){
                $mms -> process_note = $pro -> process_note;
            }
            $mms -> assignee_name = $assignee -> user_name;
            $mms -> status = $missionStatus -> status_name;
        }
        return Result::returnResult(Result::SUCCESS,$resultMinute);
    }

    /**
     * 查询所有会议信息
     * @param int $limit
     * @param int $page
     * @param string $keyword
     * @return array
     */
    public function getAllMinute($limit = 10,$page = 1,$keyword = ""){
        $minute = new Minute();
        try {
            if($keyword != ""){
                $minute -> where("minute_id",$keyword)
                        -> whereOr("minute_theme","like","%$keyword%");
            }
            $count = $minute -> count();
            if($keyword != ""){
                $minute -> where("minute_id","like",$keyword)
                        -> whereOr("minute_theme","like","%$keyword%");
            }
            $minuteList = $minute -> field("minute_id,minute_theme,host_id")
                    -> page($page,$limit)
                    -> select();
            foreach ($minuteList as $m){
                $m ->host_name = $m -> user -> user_name;
            }
            return Result::returnResult(Result::SUCCESS,$minuteList,$count);
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        } catch (Exception $e) {
        }
    }

    /**
     * 保存新发起的会议
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function saveMinute(){
        $info               = Session::get("info");
        $minuteType         = $_POST["minute_type"];        //会议类型
        $hostId             = $info["user_id"];           //会议主持id
        $departmentId       = $info["department_id"]; //所属部门id
        $minuteTheme        = $_POST["minute_theme"];     //会议标题
        $projectCode        = $_POST["project_code"];     //项目代号
        $date               = $_POST["date"];                     //会议时间
        $time               = $_POST["time"];
        $place              = $_POST["place"];                   //会议地点
        $attendUsers        = $_POST["attend_users"];     //应到人员列表
        $minuteResolution   = $_POST["minute_resolution"];   //会议决议
        $minuteContext      = $_POST["minute_context"];         //会议内容
//        $attachmentList = $_POST["attachment_list"];
        $uploadList         = input('post.file/a');//$_POST["file"];
        //保存会议基本信息
        $minute = new Minute();
        $minute -> department_id = $departmentId;
        $minute -> minute_theme = $minuteTheme;
        $minute -> project = $projectCode;
        $minute -> minute_date = $date;
        $minute -> minute_time = $time;
        $minute -> place = $place;
        $minute -> host_id = $hostId;
        $minute -> resolution = $minuteResolution;
        $minute -> record = $minuteContext;
        $minute -> minute_type = $minuteType;
        $minute -> save();
        $minuteId = $minute -> minute_id;
        //保存会议应到人员
        foreach ($attendUsers as $attend){
            $minuteAttend = new MinuteAttend();
            $minuteAttend -> minute_id = $minuteId;
            $minuteAttend -> user_id = $attend;
            $minuteAttend ->save();
        }
        //保存上传附件信息
        if(is_array($uploadList)){
            foreach ($uploadList as $file){
                $attachment = new Attachment();
                $attachment -> where("attachment_id",$file)
                    ->update(['attachment_type' => "minute","related_id" => $minuteId]);
            }
        }
        //删除临时保存的会议信息
        //删除临时表里的应到人员
        $minuteTemp = new MinuteTemp();
        $m = $minuteTemp -> where(['minute_id' => 0,'host_id' => $hostId]) ->find();
        $m -> minuteAttends;
        $m -> minuteAttends()->delete();
        $m -> delete();
        if($minuteId != null){
            return Result::returnResult(Result::SUCCESS,null);
        }
        return Result::returnResult(Result::ERROR,null);
    }

    /**
     * 修改会议
     */
    public function updateMinute(){
        $minuteId           = $_POST["minuteId"];
        $attended           = input('post.attendList/a');//$_POST["attendList"];                     //实际到会人员
        $newAttend          = input('post.newAttended/a');//$_POST["newAttended"];                 //新增应到人员
        $newMission         = input('post.newMission/a');//$_POST["newMission"];                 //新增基本任务清单
        $minuteResolution   = $_POST["minuteResolution"];     //会议决议
        $minuteContext      = $_POST["minuteContext"];           //会议记录
        $minuteMission      = input('post.minuteMission/a');           //添加会议任务纪要(前端页面未实现)
        $uploadList         = input('post.uploadList/a');//$_POST["uploadList"];                 //上传的附件
        //保存会议基本信息
        $minute = new Minute();
        $minute -> where("minute_id",$minuteId);
        $minute -> resolution = $minuteResolution;
        $minute -> record = $minuteContext;
        $minute -> save();
        if(is_array($attended)){
            foreach ($attended as $att){
                $minuteAttend = new MinuteAttend();
                $minuteAttend -> where("minute_id",$minuteId)
                    -> where("user_id",$att)
                    -> setField('status', 1);
            }
        }
        if(is_array($newAttend)){
            foreach ($newAttend as $att){
                $minuteAttend = new MinuteAttend();
                $minuteAttend -> minute_id = $minuteId;
                $minuteAttend -> user_id = $att;
                $minuteAttend -> save();
            }
        }
        if(is_array($newMission)){
            foreach ($newMission as $mis){
                $minuteMission = new MinuteMission();
                $minuteMission -> minute_id = $minuteId;
                $minuteMission -> mission_id = $mis;
                $minuteMission ->save();
            }
        }
        if(is_array($minuteMission)){
            foreach($minuteMission as $mis){
                $mission = new Mission();
                $mission -> mission_title = $mis['missionTitle'];
                $mission -> assignee_id   = $mis['assigneeId'];
                $mission -> finish_date   = $mis['finishDate'];
                $mission -> description   = $mis['description'];
                $mission -> minute_id     = $minuteId;
                $mission -> save();
            }
        }
        if(is_array($uploadList)){
            foreach ($uploadList as $file){
                $attachment = new Attachment();
                $attachment -> where("attachment_id",$file)
                    ->update(['attachment_type' => "minute","related_id" => $minuteId]);
            }
        }
        return Result::returnResult(Result::SUCCESS,null);
    }

    /**
     * 查询所有部门id和部门名字
     * @return array
     */
    public function getAllDepartment(){
        $department = new Department();
        try {
            $departmentList = $department -> field("department_id,department_name")
                                          -> select();
            return Result::returnResult(Result::SUCCESS,$departmentList);
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
        return Result::returnResult(Result::ERROR,null);
    }

    /**
     * 根据部门id查询员工
     * @param $departmentId
     * @return array
     */
    public function getUserByDepartment($departmentId){
        $userList =  new User();
        try {
            $userList -> where("department_id", $departmentId)
                      -> where("user_status", 1)
                      -> field("user_id,user_name")
                      -> select();
            return Result::returnResult(Result::ERROR,$userList);
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
    }

    /**
     * 查看是否有临时保存的会议信息
     */
    public function hasTempMinute(){
        $info = Session::get("info");
        $user_id = $info["user_id"];
        $minuteTemp =  new MinuteTemp();
        try {
            $resuleMinute = $minuteTemp -> where("host_id", $user_id)
                                        -> where("status", 0)
                                        -> find();
            if($resuleMinute!=null){
                $resuleMinutes = $resuleMinute -> minuteAttends;
                foreach ($resuleMinutes as $rm){
                    $rm -> user;
                }
                return Result::returnResult(Result::SUCCESS,$resuleMinute);
            }
            return Result::returnResult(Result::NOT_MINUTE_TEMP,null);
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
    }

    /**
     * 临时保存新增页面会议信息到临时会议表
     */
    public function saveTemp(){
        $info               = Session::get("info");
        $minuteType         = $_POST["minute_type"];        //会议类型
        $hostId             = $info["user_id"];           //会议主持id
        $departmentId       = $info["department_id"]; //所属部门id
        $minuteTheme        = $_POST["minute_theme"];     //会议标题
        $projectCode        = $_POST["project_code"];     //项目代号
        $date               = $_POST["date"];                     //会议时间
        $time               = $_POST["time"];
        $place              = $_POST["place"];                   //会议地点
        $attendUsers        = input('post.attend_users/a');    //应到人员列表
        $minuteResolution   = $_POST["minute_resolution"];   //会议决议
        $minuteContext      = $_POST["minute_context"];         //会议内容
        $minuteTemp = MinuteTemp::get(['host_id' => $hostId,'status' => 0]);
        try {
            if( $minuteTemp == null){
                $minuteTemp = new MinuteTemp();
            }
            $minuteTemp -> department_id = $departmentId;
            $minuteTemp -> minute_theme = $minuteTheme;
            $minuteTemp -> project = $projectCode;
            $minuteTemp -> minute_date = $date;
            $minuteTemp -> minute_time = $time;
            $minuteTemp -> place = $place;
            $minuteTemp -> host_id = $hostId;
            $minuteTemp -> resolution = $minuteResolution;
            $minuteTemp -> record = $minuteContext;
            $minuteTemp -> minute_type = $minuteType;
            $result = $minuteTemp -> save();
            if(is_array($attendUsers)){
                //保存之前先把之前的删除，否则会有人员重复现象
                MinuteAttendTemp::destroy(['minute_id' => $minuteTemp -> id]);
                foreach ($attendUsers as $att){
                    $minuteAttendTemp = new MinuteAttendTemp();
                    $minuteAttendTemp -> minute_id = $minuteTemp -> id;
                    $minuteAttendTemp -> user_id = $att;
                    $minuteAttendTemp -> save();
                }
            }
            if($result == 1){
                return Result::returnResult(Result::SUCCESS,null);
            }
            return Result::returnResult(Result::ERROR,null);
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
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