<?php
/**
 * Created by PhpStorm.
 * User: TZX
 * Date: 2020/10/12
 * Time: 14:58
 */

namespace app\index\controller;

use app\common\Result;
use app\common\util\curlUtil;
use app\index\model\User;

class AdminC
{
    // 更新用户钉钉userid
    public function updateUserid()
    {
        // 获取所有用户 userid
        $res = curlUtil::post('http://www.bjzzdr.top/us_service/public/other/ding_ding_c/getAllUserId');

        if($res->code == 0) {
            foreach ($res->data as $info) {
                $user = User::getByUserName($info->name);
                if($user) {
                    $user->dd_userid = $info->userid;
                    $user->save();
                }
            }
        }


        return Result::returnResult(Result::SUCCESS);
    }
}