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
    /*
     * 工号和名字相互转换
     * 1：工号转名字，2：名字转工号
     */
    public static function userIdToName($string, $type) {
        if($type = 1) {
            $user = User::get(['user_id' => $string]);
            return $user->user_name;
        } else {
            $user = User::get(['user_name' => $string]);
            return $user->user_id;
        }
    }

    // 判断用户是否是管理员
    public static function isAdmin($userId) {
        $role = Role::get(['role_name' => '管理员']);
        $userRole = UserRole::get(['user_id' => $userId, 'role_id' => $role->role_id]);
        if($userRole) {
            return true;
        } else {
            return false;
        }
    }
}