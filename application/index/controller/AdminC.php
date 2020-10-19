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
use think\Session;

class AdminC
{
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