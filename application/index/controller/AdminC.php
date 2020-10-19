<?php
/**
 * Created by PhpStorm.
 * User: TZX
 * Date: 2020/10/12
 * Time: 14:58
 */

namespace app\index\controller;

use app\common\Result;
use app\common\util\curlUtil;
use app\common\util\EncryptionUtil;
use app\index\model\User;
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
                if($user) {
                    $user->dd_userid = $info->userid;
                    $user->save();
                }
            }
        }
        return Result::returnResult(Result::SUCCESS);
    }

    /**
     * 管理元添加用户
     */
    public function addUser(){
        $userId       = $_POST["user_id"];
        $userName     = $_POST["user_name"];
        $departmentId = $_POST["department_id"];
        $phone        = $_POST["phone"];
        $email        = $_POST["email"];
        $password     = EncryptionUtil::Md5Encryption("123",$userId);
        $info = Session::get("info");
        $hasSuper = $info["super"];
        //判断当前用户是否为管理员
        if($hasSuper === 0){
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
            'email'          =>  $email
        ]);
        $result = $user->save();
        if($result > 0){
            return Result::returnResult(Result::SUCCESS);
        }
        return Result::returnResult(Result::ERROR);
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


}