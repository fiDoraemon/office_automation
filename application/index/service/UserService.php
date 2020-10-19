<?php
/**
 * Created by PhpStorm.
 * User: TZX
 * Date: 2020/10/19
 * Time: 10:31
 */

namespace app\index\service;


use app\index\model\User;

/**
 * 用户服务类
 * Class UserService
 * @package app\index\service
 */
class UserService
{
    /**
     * 判断用户是否是管理员
     * @param $userId
     * @return bool
     * @throws \think\exception\DbException
     */
    public static function isSuper($userId) {
        $user = User::get(['user_id' => $userId]);

        if($user->super == 1) {
            return true;
        } else {
            return false;
        }
    }
}