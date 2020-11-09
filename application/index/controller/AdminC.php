<?php
/**
 * Created by PhpStorm.
 * User: TZX
 * Date: 2020/10/12
 * Time: 14:58
 */

namespace app\index\controller;

use app\common\model\USDepartment;
use app\common\model\UserInfo;
use app\common\model\USUser;
use app\common\Result;
use app\common\util\curlUtil;
use app\common\util\EncryptionUtil;
use app\index\model\Department;
use app\index\model\Minute;
use app\index\model\User;
use app\index\model\UserRole;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Session;

class AdminC
{
    /**
     * 查询所有状态正常的用户
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\Exception
     */
//    public function getAllUser($page = 1,$limit = 15){
//        try {
//            $user = new User();
//            $user -> where("user_status",1);
//            $count =  $user -> count();
//            $user = new User();
//            $listUser = $user -> where("user_status",1)
//                  -> field("user_id,user_name,department_id,phone,email,user_status,create_time,update_time")
//                  -> page($page,$limit)
//                  -> order('user_status desc,department_id')
//                  -> select();
//            foreach ($listUser as $u){
//                $u -> department_name = $u -> department -> department_name;
//                if($u -> user_status === 1){
//                    $u -> status = "正常";
//                }else{
//                    $u -> status = "停用";
//                }
//            }
//            return Result::returnResult(Result::SUCCESS, $listUser,$count);
//        } catch (DataNotFoundException $e) {
//        } catch (ModelNotFoundException $e) {
//        } catch (DbException $e) {
//        }
//    }

    /**
     * 根据用户姓名,部门,状态查询用户
     * @param string $userName
     * @param int $departmentId
     * @param int $userStatus
     * @param int $page
     * @param int $limit
     * @return array
     * @throws \think\Exception
     */
    public function getAllUsers($userName = "",$departmentId = 0,$userStatus = 1,$page = 1,$limit = 15){
        try {
            $user = new User();
            $user -> where("user_status",$userStatus);
            if($departmentId != 0){
                $user -> where("department_id",$departmentId);
            }
            if($userName != ""){
                $user ->  where("user_name","like","%$userName%");
            }
            $count =  $user -> count();
            $user -> where("user_status",$userStatus);
            if($departmentId != 0){
                $user -> where("department_id",$departmentId);
            }
            if($userName != ""){
                $user ->  where("user_name","like","%$userName%");
            }
            $listUser = $user -> field("user_id,user_name,department_id,phone,email,user_status,create_time,update_time")
                              -> page($page,$limit)
                              -> order('user_status desc,department_id')
                              -> select();
            foreach ($listUser as $u){
                $u -> department_name = $u -> department -> department_name;
                if($u -> user_status === 1){
                    $u -> status = "正常";
                }else{
                    $u -> status = "停用";
                }
            }
            return Result::returnResult(Result::SUCCESS, $listUser,$count);
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
    }

    /**
     * 获取所有的会议信息
     * @param int $page
     * @param int $limit
     * @param int $projectCode
     * @param int $minuteType
     * @param string $keyword
     * @param int $isMyLaunch
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     * @throws \think\Exception
     */
    public function getAllMeet($page = 1,$limit = 15,$projectCode = 0,$minuteType = -1,$keyword = "",$isMyLaunch = 0){
        $userId = Session::get("info")["user_id"];
        //查询对应的会议信息
        $minutes = new Minute();
        //是我发起的会议
        if($isMyLaunch == 1){
            $minutes -> where("host_id",$userId);
        }
        //模糊查询条件
        if($keyword != ""){
            $minutes -> where("minute_theme","like","%$keyword%");
        }else{
            //对项目代号进行查询
            if($projectCode != 0){
                $minutes -> where("project_id",$projectCode);
            }
            //对会议类型进行查询
            if($minuteType != -1){
                $minutes -> where("minute_type",$minuteType);
            }
        }
        //查询共有多少条符合条件的数据(分页需要使用到)
        $count =  $minutes -> count();
        if($isMyLaunch == 1){
            $minutes -> where("host_id",$userId);
        }
        if($keyword != ""){
            $minutes -> where("minute_theme","like","%$keyword%");
        }else{
            //对项目代号进行查询
            if($projectCode != 0){
                $minutes -> where("project_id",$projectCode);
            }
            //对会议类型进行查询
            if($minuteType != -1){
                $minutes -> where("minute_type",$minuteType);
            }
        }
        $listMineMeet = $minutes -> field("minute_id,minute_theme,host_id,minute_date,minute_time,project_id,minute_type,review_status,project_stage")
                                 -> page($page,$limit)
                                 -> order("minute_id","desc")
                                 -> select();
        foreach ($listMineMeet as $minute){
            $users = "";                                                            //所有应到的员工
            $minute -> minute_host  = $minute -> user         -> user_name;         //会议主持人名字
            $minute -> minute_type  = $minute -> minuteType   -> type_name;         //会议类型名字
            $minute -> review_name  = $minute -> minuteReview -> review_name;       //会议评审状态
            $minute -> project_code = $minute -> project      -> project_code;      //项目代号
            $minute -> stage_name   = $minute -> projectStage -> stage_name;        //项目阶段
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
     * 更新用户钉钉userid
     * @return array
     */
    public function updateUserid()
    {
        // 获取所有用户 userid
        $res = curlUtil::post('http://www.bjzzdr.top/us_service/public/other/ding_ding_c/getAllUserId');
        if($res->code == 0) {
            foreach ($res->data as $info) {
                $user = User::getByUserName($info->name);
                if($user && $user->dd_userid == '') {
                    $user->dd_userid = $info->userid;
                    $user->save();
                }
            }
        }
        return Result::returnResult(Result::SUCCESS);
    }

    /**
     * 管理员添加用户
     */
    public function addUser(){
        $userId       = $_POST["user_id"];
        $userName     = $_POST["user_name"];
        $departmentId = $_POST["department_id"];
        $phone        = $_POST["phone"];
        $email        = $_POST["email"];
        $password     = EncryptionUtil::Md5Encryption("123",$userId);
        $info = Session::get("info");
        $sessionUserId = $info["user_id"];
        //判断当前用户是否为管理员
        $isAdmin = $this -> isAdmin($sessionUserId);
        if(!$isAdmin){
            return Result::returnResult(Result::NO_ACCESS);
        }
        //判断员工id是否已经存在
        if($this -> checkHasUser($userId)){
            return Result::returnResult(Result::EXIST_USER);
        }
        //向数据库插入数据
        $user = new User([
            'user_id'        =>  $userId,
            'user_name'      =>  $userName,
            'password'       =>  $password,
            'department_id'  =>  $departmentId,
            'phone'          =>  $phone,
            'email'          =>  $email,
            'create_time'    =>  date('Y-m-d H:i:s', time())
        ]);
        $result = $user->save();

        // 更新所有用户 userid
        $this->updateUserid();
        // 同步添加旧 OA 用户
        $department = Department::get($departmentId);
        $user = User::get(['user_id' => $userId]);
        $password = 'XAPUHUECKGGSEXISXIPS';
        $userInfo = new UserInfo();
        $userInfo->data([
            'Name'       => $userName,
            'User_ID'    => $userId,
            'Password'   => $password,
            'email'      => $email,
            'userid'     => $user->dd_userid,
            'department' => $department->department_name
        ]);
        $userInfo->save();
        // 同步添加阿里云数据库用户
        $USDepartment = USDepartment::get(['department_name' => $department->department_name]);
        $USUser = new USUser();
        $USUser->user_id = $userId;
        $USUser->user_name = $userName;
        $USUser->department_id = $USDepartment->department_id;
        $USUser->userid = $user->dd_userid;
        $USUser->is_us = 0;             // 默认不能访问用服系统
        $USUser->save();

        if($result > 0) {
            return Result::returnResult(Result::SUCCESS);
        }
        return Result::returnResult(Result::ERROR);
    }

    /**
     * 禁用用户
     */
    public function deleteUser(){
        $userId = $_POST["user_id"];
        $user = new User;
        $user -> where('user_id', $userId)
              -> update(['user_status' => 0]);

        // 同步停用旧 OA 用户
        $userInfo = UserInfo::get(['User_ID' => $userId]);
        if($userInfo) {
            $userInfo->obsolete = 1;
            $userInfo->save();
        }
        // 同步停用阿里云数据库的用户
        $USUser = USUser::get(['user_id' => $userId]);
        if($USUser) {
            $USUser->obsolete = 1;
            $USUser->save();
        }

        return Result::returnResult(Result::SUCCESS);
    }

    public function getUserInfo(){
        $userId = $_GET["userId"];
        $userInfo = User::where('user_id','=',$userId)
                        -> field("id,user_id,user_name,email,phone,department_id")
                        -> find();
        return Result::returnResult(Result::SUCCESS,$userInfo);
    }

    /**
     * 修改用户
     */
    public function updateUser(){
        $userId = $_POST["userId"];
        $departmentId = $_POST["departmentId"];
        $userName = $_POST["userName"];
        $phone = $_POST["phone"];
        $email = $_POST["email"];
        $user = new User();
        $user -> where('user_id', $userId)
              -> update([
                  'user_name'     => $userName,
                  'department_id' => $departmentId,
                  'phone'         => $phone,
                  'email'         => $email]);

        // 同步修改旧 OA 用户
        $department = Department::get($departmentId);
        $userInfo = UserInfo::get(['User_ID' => $userId]);
        if($userInfo) {
            $userInfo->data([
                'Name'       => $userName,
                'email'      => $email,
                'department' => $department->department_name
            ]);
            $userInfo->save();
        }
        // 同步添加阿里云数据库用户
        $USDepartment = USDepartment::get(['department_name' => $department->department_name]);
        $USUser = USUser::get(['user_id' => $userId]);
        if($USUser) {
            $USUser->user_name = $userName;
            $USUser->department_id = $USDepartment->department_id;
            $USUser->save();
        }

        return Result::returnResult(Result::SUCCESS);
    }

    /**
     * 查看是否已经含有某一个用户
     */
    private function checkHasUser($userId){
        $user = User::get(['user_id' => $userId]);
        if($user != null){
            return true;
        }
        return false;
    }

    /**
     * 判断是否为管理员
     * @param $userId
     * @return bool
     * @throws DbException
     */
    private function isAdmin($userId){
        $userRole = UserRole::get(["user_id" => $userId, "role_id" => 3]);
        // 如果不是管理员
        if($userRole == null) {
           return false;
        }
        return true;
    }
}