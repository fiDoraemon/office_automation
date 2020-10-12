<?php
/**
 * Created by PhpStorm.
 * User: TZX
 * Date: 2020/10/12
 * Time: 14:58
 */

namespace app\index\controller;

use app\common\util\curlUtil;

class AdminC
{
    // 更新用户钉钉userid
    public function updateUserid()
    {
        // 获取所有用户 userid
        $result = curlUtil::post('http://www.bjzzdr.top/us_service/public/other/ding_ding_c/getAllUserId');
        $userInfo = array();

        foreach ($userInfo as $user) {
            $user = User::getByUserName($user['name']);
            if($user) {
                $user->userid = $user['userid'];
                $user->save();
            }
        }

        return Result::returnResult(Result::SUCCESS);
    }
}