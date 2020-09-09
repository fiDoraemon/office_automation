<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/9/7 0007
 * Time: 上午 11:07
 */

namespace app\index\controller;

use app\common\interceptor\CommonController;
use app\common\model\User;
use app\common\Result;
use app\common\util\EncryptionUtil;
use think\exception\DbException;
use think\Session;

/**
 * Class LoginController
 * @package app\index\controller
 *
 * 用户登录信息操作
 */
class LoginController //extends CommonController
{
    //测试
    public function loginTest(){
        $result = $this -> checkUser(10086, "123456");
        if($result){
            return "查询成功";
        }
        return "查询失败";
    }

    public function testData(){
        return  EncryptionUtil::Md5Encryption(123456,10086);
    }

    /**
     * @return array
     * 用户登录
     */
    public function login(){
        $userNum = $_POST["userNum"];
        $userPwd = EncryptionUtil::Md5Encryption($_POST["userPwd"],$userNum);
        try {
            $checkResult = $this->checkUser($userNum, $userPwd);
            if($checkResult){
                $userInfo = Session::get('info');
                return Result::returnResult(Result::SUCCESS,$userInfo);
            }else{
                return Result::returnResult(Result::NO_USER_INFO,null);
            }
        } catch (DbException $e) {
            return Result::returnResult(Result::ERROR,null);
        }
    }

    /**
     * 用户注销
     *
     */
    public function loginOut(){
        Session::set('info',null);
        return Result::returnResult(Result::SUCCESS,null);
    }

    /**
     * @param $userNum  用户工号
     * @param $userPwd  用户密码
     * @return bool     用户合法返回true并且保存在session种，非法返回false;
     * @throws \think\exception\DbException
     *  验证用户合法性
     */
    private function checkUser($userNum, $userPwd){
        $user = User::get(['user_id' => $userNum ,'password' => $userPwd ,'user_status' => 1]);
        if(is_null($user)){
            return false;
        }else{
            //登录成功，保存信息到session中
            Session::set('info',$user);
            return true;
        }
    }


}