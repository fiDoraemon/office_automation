<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/9/8 0008
 * Time: 上午 10:24
 */

namespace app\index\controller;


use app\common\interceptor\CommonController;
use app\common\Result;
use think\Session;

class UserController extends CommonController
{
    /**
     * @return array
     * 修改密码
     */
    public function changePwd(){
        $oldPwd = $_POST["oldPwd"];
        $newPwd = $_POST["newPwd"];
        $user = Session::get('info');
        //检查旧密码与填写得是否一致
        if($oldPwd === $user->password){
            $user->password = $newPwd;
            $saveCount = $user->save();
            if($saveCount == 1){
                Session::set('info',$user);
                return Result::returnResult(Result::SUCCESS,null);
            }
            return Result::returnResult(Result::ERROR,null);
        }
        return Result::returnResult(Result::OLD_PASSWORLD_ERROR,null);
    }


    /**
     * @return array
     * 获取用户信息
     */
    public function getUserInfo(){
        $user =  Session::get("info");
        return Result::returnResult(Result::SUCCESS,$user);
    }

    /**
     * 修改用户信息
     */
    public function updateUserInfo(){
        $userName = $_POST["userName"];
        $userPhone = $_POST["userPhone"];
        $userEmail = $_POST["userEmail"];
        $user = Session::get("info");
        $user->user_name = $userName;
        $user->phone = $userPhone;
        $user->email = $userEmail;
        $updateResult = $user->save();
        if($updateResult === 1){
            Session::set("info",$user);
            return Result::returnResult(Result::SUCCESS,null);
        }
        return Result::returnResult(Result::ERROR,null);
    }
}