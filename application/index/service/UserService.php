<?php
/**
 * Created by PhpStorm.
 * User: TZX
 * Date: 2020/10/19
 * Time: 10:31
 */

namespace app\index\service;


use app\index\model\Role;
use app\index\model\User;
use app\index\model\UserRole;

/**
 * 用户服务类
 * Class UserService
 * @package app\index\service
 */
class UserService
{
    /**
     * 工号和名字相互转换
     * @param $string
     * @param $type 1 工号转名字，2 名字转工号
     * @return mixed
     * @throws \think\exception\DbException
     */
    public static function userIdToName($string, $type = 1) {
        if($type == 1) {
            $user = User::get(['user_id' => $string]);
            return $user->user_name;
        } else {
            $user = User::get(['user_name' => $string]);
            return $user->user_id;
        }
    }

    /**
     * 判断用户是否是管理员
     * @param $userId
     * @return bool
     * @throws \think\exception\DbException
     */
    public static function isAdmin($userId) {
        $role = Role::get(['role_name' => 'admin']);
        $userRole = UserRole::get(['user_id' => $userId, 'role_id' => $role->role_id]);
        if($userRole) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 判断名字是否合法
     * @param $userName
     * @return bool
     * @throws \think\Exception
     */
    public static function isRightName($userName) {
        $user = new User();
        $count = $user->where('user_name', $userName)->count();
        if($count > 1 || $count == 0) {
            return false;
        } else {
            return true;
        }
    }
}