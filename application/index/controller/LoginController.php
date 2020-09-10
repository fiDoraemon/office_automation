<?php
/**
 * Created by PhpStorm.
 * User: Conqin
 * Date: 2020/9/7 0007
 * Time: 上午 11:07
 */

namespace app\index\controller;


use app\common\model\User;
use app\common\Result;
use app\common\util\EncryptionUtil;
use app\common\util\SendmailUtil;
use think\captcha\Captcha;
use think\exception\DbException;
use think\Session;

/**
 * Class LoginController
 * @package app\index\controller
 *
 * 用户登录信息操作
 */
class LoginController
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

    public function getPageCode(){
        return Result::returnResult(Result::CODE_ERROR,Session::get('verify_code'));
    }

    /**
     * 发送登录页面验证码
     */
    public function sendPageCode(){
        $captcha = new captcha();//captcha 验证码初始化
        $codeImg = $captcha ->entry();
        return $codeImg;
    }

    /**用户登录
     * @return array
     */
    public function login(){
        $pageCode = $_POST["pageCode"];
        if(!$this->checkPageCode($pageCode)){
            return Result::returnResult(Result::CODE_ERROR,null);
        }
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

    /** 用户注销
     * @return array
     */
    public function loginOut(){
        Session::set('info',null);
        return Result::returnResult(Result::SUCCESS,null);
    }


    /**
     * 找回密码
     */
    public function retrievePwd(){
        $code   = $_POST["code"];
        $newPwd = $_POST["newPwd"];
        $userId = $_POST["userId"];
        //校验验证码
        $entryCode = $this -> checkEmailCode($code);
        if($entryCode){//更改数据库信息
            $newPwd = EncryptionUtil::Md5Encryption($newPwd,$userId);
            try {
                $user = User::get(['user_id' => $userId, 'user_status' => 1]);
            } catch (DbException $e) {
                return Result::returnResult(Result::RETRIEVE_PASSWORLD_FAIL,null);
            }
            $user->password = $newPwd;
            $user->save();
            return Result::returnResult(Result::RETRIEVE_PASSWORLD_SUCCESS,null);
        }else{
            return Result::returnResult(Result::CODE_ERROR,null);
        }
    }

    /**
     * 发送邮箱验证码
     */
    public function sendEmailCode(){
        $userId = $_POST["userId"];
        $userEmail = $_POST["userEmail"];
        $codeType = $_POST["codeType"];
        if($codeType == 1){  //邮箱验证
            $email = User::where('user_id',$userId)->value('email');
            if($email === $userEmail){
                //发送验证码
                $code = $this->getCode();
                $sendResult = SendmailUtil::sendCodeEmail($email,$userId,$code);
                if($sendResult){ //发送成功
                    Session::set("emailCode",$code);
                    return Result::returnResult(Result::SEND_CODE_SUCCESS,null);
                }
            }else{
                return Result::returnResult(Result::EMAIL_ERROR,null);
            }
        }
        //发送失败
        return Result::returnResult(Result::SEND_CODE_ERROR,null);
    }

    /** 验证前端页面验证码
     *
     */
    private function checkPageCode($pageCode){
        // 检测输入的验证码是否正确，$value为用户输入的验证码字符串
        $captcha = new Captcha();
        if($captcha->check($pageCode)){
            return true;
        }
        return false;
    }

    /** 验证用户合法性
     * @param $userNum  用户工号
     * @param $userPwd  用户密码
     * @return bool     用户合法返回true并且保存在session种，非法返回false;
     * @throws \think\exception\DbException
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

    /** 随机生成6位数字验证码
     * @return string 返回6位由数字和大小写字母组成的验证码
     */
    private function getCode(){
        //定义一个验证码池，验证码由其中几个字符组成
        $pool='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $word_length=6;//验证码长度
        $code = '';//验证码
        for ($i = 0, $mt_rand_max = strlen($pool) - 1; $i < $word_length; $i++)
        {
            $code .= $pool[mt_rand(0, $mt_rand_max)];
        }
        return $code;
    }

    /**
     * 校验邮箱验证码
     */
    private function checkEmailCode($code){
        $emailCode = Session::get("emailCode");
        if($code == $emailCode){  //验证码正确
            //删除session里面的验证码信息
            unset($_SESSION['code']);
            return true;
        }
        return false;
    }


}