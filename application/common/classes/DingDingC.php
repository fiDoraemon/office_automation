<?php
/**
 * Created by PhpStorm.
 * User: TZX
 * Date: 2020/10/12
 * Time: 13:51
 */

namespace app\other\controller;

// 提供给OA系统使用的钉钉控制器
use app\common\Result;

class DingDingC
{
    // 获取所有用户钉钉 userid
    public function getAllUserId() {
        $dingding = new \DingDing();
        $userInfo = $dingding->getAllUserId();

        if(empty($userInfo)) {
            return json_encode(Result::returnResult(Result::ERROR));
        } else {
            return json_encode(Result::returnResult(Result::SUCCESS, $userInfo));
        }
    }

    // 发送钉钉消息
    public function sendMessage() {
        $userList = input('post.userList');
        $data = input('post.data/a');

        if($userList == '' || $data == '') {
            return Result::returnResult(Result::ERROR);
        }

        $dingding = new \DingDing();
        $result = $dingding->sendMessage($userList, $data);

        if($result == true) {
            return json_encode(Result::returnResult(Result::SUCCESS));
        } else {
            return json_encode(Result::returnResult(Result::ERROR));
        }
    }
}