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
        $data = input('post.data');
//        $data = [
//            'head' => 'OA通知',
//            'title' => '了解钉钉接口',
//            'detail'=> [
//                ['key' => '任务号', 'value' => '1'],
//                ['key' => '描述', 'value' => '学习下如何使用钉钉接口']
//            ],
//            'file_count' => 3
//        ];

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