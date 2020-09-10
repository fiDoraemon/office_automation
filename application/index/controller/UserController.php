<?php
/**
 * Created by PhpStorm.
 * User: Conqin
 * Date: 2020/9/8 0008
 * Time: 上午 10:24
 */

namespace app\index\controller;


use app\common\interceptor\CommonController;
use app\common\Result;
use app\common\util\EncryptionUtil;
use think\Session;

class UserController extends CommonController
{
    /** 修改密码
     * @return array
     */
    public function changePwd(){
        $user = Session::get('info');
        $userId = $user->user_id;
        $oldPwd = EncryptionUtil::Md5Encryption($_POST["oldPwd"],$userId);
        $newPwd = $_POST["newPwd"];
        //加密后与原密码对比
        if($oldPwd === $user->password){
            //将新的密码加密后保存到数据库
            $newPwd = EncryptionUtil::Md5Encryption($newPwd,$userId);
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


    /** 获取用户信息
     * @return array
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