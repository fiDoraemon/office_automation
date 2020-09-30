<?php
/**
 * Created by PhpStorm.
 * User: Conqin
 * Date: 2020/9/8 0008
 * Time: 上午 10:24
 */

namespace app\index\controller;


use app\common\interceptor\CommonController;
use app\index\model\User;
use app\common\Result;
use app\common\util\EncryptionUtil;
use think\Db;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\DbException;
use think\Session;

class UserC extends CommonController
{
    /**
     * 修改密码
     * @return array
     */
    public function changePwd(){
        $user = Session::get('info');
        $userId = $user["user_id"]; //数据库的user_id
        $password = User::where('user_id',$userId)->value('password');  //数据库保存的密码
        $oldPwd = EncryptionUtil::Md5Encryption($_POST["oldPwd"],$userId);  //传过来的旧密码
        $newPwd = $_POST["newPwd"]; //传过来的新密码
        //加密后与原密码对比
        if($oldPwd === $password){
            //将新的密码加密后保存到数据库
            $newPwd = EncryptionUtil::Md5Encryption($newPwd,$userId);
            $saveCount = User::where('user_id', $userId)
                ->update(['password' => $newPwd]);
            if($saveCount == 1){
                return Result::returnResult(Result::SUCCESS,null);
            }
            return Result::returnResult(Result::ERROR,null);
        }
        return Result::returnResult(Result::OLD_PASSWORLD_ERROR,null);
    }

    /**
     * 查询某个部门的所有员工
     * @param int $departmentId 部门id
     * @return array
     */
    public function getUserByDepartment($departmentId = 0){
        if($departmentId != 0){
            $user = new User();
            try {
                $userList = $user->where("department_id", $departmentId)
                    ->where("user_status", 1)
                    ->field("user_id,user_name")
                    ->select();
                return Result::returnResult(Result::SUCCESS,$userList);
            } catch (DataNotFoundException $e) {
            } catch (ModelNotFoundException $e) {
            } catch (DbException $e) {
            }
        }
    }

    /**
     * 获取用户信息
     * @return array
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public function getUserInfo(){
        $user =  Session::get("info");
        $userInfo = Db::table('oa_user')
            ->field("id,user_id,user_name,email,phone,department_id")
            ->where('user_id','=',$user["user_id"])
            ->find();
        return Result::returnResult(Result::SUCCESS,$userInfo);
    }

    /**
     * 修改用户信息
     */
    public function updateUserInfo(){
        $userName = $_POST["userName"];
        $userPhone = $_POST["userPhone"];
        $userEmail = $_POST["userEmail"];
        $user = Session::get("info");
        $userId = $user["user_id"];
        $updateResult = User::where('user_id', $userId)
            ->update(['user_name' => $userName,
                'phone' => $userPhone,
                'email' => $userEmail,
            ]);
        if($updateResult == 1){
            $this -> updateInfo();//更新session中的用户信息
            return Result::returnResult(Result::SUCCESS,null);
        }
        return Result::returnResult(Result::ERROR,null);
    }

    /**
     * 添加用户
     */
    public function addUser(){
        $userId       = $_POST["userId"];
        $userName     = $_POST["userName"];
        $password     = $_POST["password"];
        $password     = EncryptionUtil::Md5Encryption($password,$userId); //密码加密保存到数据库
        $phone        = $_POST["phone"];;
        $email        = $_POST["email"];
        $ddUserid     = $_POST["ddUserid"];;
        $pepartmentId = $_POST["pepartmentId"];
        $user = new User;
        $user->userId       = $userId;
        $user->userName     = $userName;
        $user->password     = $password;
        $user->phone        = $phone;
        $user->email        = $email;
        $user->ddUserid     = $ddUserid;
        $user->pepartmentId = $pepartmentId;
        $result = $user -> save();
        if($result == 1){
            return Result::returnResult(Result::USER_ADD_SUCCESS,null);
        }
        return Result::returnResult(Result::USER_ADD_ERROR,null);
    }

    /**
     * 查询所有用户部分信息
     * @return array
     */
    public function selectAllUser(){
        try {
            $userList = Db::table('oa_user')
                ->where('user_status', 1)
                ->field("id,user_id,user_name,phone,email,dd_userid,department_id")
                ->select();
            return Result::returnResult(Result::USER_SELECT_SUCCESS,$userList);
        } catch (DbException $e) {
        }
        return Result::returnResult(Result::USER_SELECT_ERROR,null);
    }

    /**
     * 根据用户的名字进行模糊查询
     */
    public function selectVagueUsers(){
        $val = $_POST["vague"];
        $val = "%" . $val . "%";
        try {
            $userList = Db::table('oa_user')
                ->where('user_name', 'like', $val)
                ->where('user_status', 1)
                ->field("id,user_id,user_name,phone,email,dd_userid,department_id")
                ->select();
            return Result::returnResult(Result::SUCCESS,$userList);
        } catch (DataNotFoundException $e) {
        } catch (ModelNotFoundException $e) {
        } catch (DbException $e) {
        }
        return Result::returnResult(Result::ERROR,null);
    }

    /**
     * 删除用户（修改用户的状态为0）
     * @throws \think\exception\DbException
     */
    public function deleteUser(){
        $userId = $_POST["userId"];
        $user = User::get(['user_id' => $userId ,'user_status' => 1]);
        $user -> user_status = 0;
        $delResult = $user->save();
        if($delResult == 1){
            return Result::returnResult(Result::USER_DELETE_SUCCESS,null);
        }
        return Result::returnResult(Result::USER_DELETE_ERROR,null);
    }

    /**
     * 更新sesion中的用户信息
     */
    private function updateInfo(){
        $user = Session::get("info");
        $userId = $user["user_id"];
        $userInfo = Db::table('oa_user')
            ->field("id,user_id,user_name,dd_userid,department_id,token,token_time_out")
            ->where('user_id','=',$userId)
            ->find();
        Session::set("info",$userInfo);
    }

}