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
}