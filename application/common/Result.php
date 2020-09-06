<?php

namespace app\common;

class Result
{
    // 状态码
    const SUCCESS               = 0;
    const ERROR                 = 1;
    const NO_USER_INFO         = 2;
    const NO_ACCESS            = 3;
    const FORBIDDEN_IP          = 4;
    const NOT_LOGIN             = 5;
    const OBJECT_EXIST          = 6;
    const FORBIDDEN_USER        = 7;

    // 对应结果
    public static $resultMsg = [
        self::SUCCESS             => '请求成功！',
        self::ERROR               => '请求错误！',
        self::NO_USER_INFO       => '数据库没有您的用户信息！',
        self::NO_ACCESS          => '您无权限查看!',
        self::NOT_LOGIN          => '您还未登录！',
        self::OBJECT_EXIST      => '对象已存在！',
        self::FORBIDDEN_USER    => '您的账户已禁用！'
    ];

    // 返回结果
    public static function returnResult($code, $data = []) {
        $result = [
            'code' => $code,
            'msg' => self::$resultMsg[$code],
            'data' => $data,
            'time' => $_SERVER['REQUEST_TIME']
        ];
        return $result;
    }
}