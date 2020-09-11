<?php

namespace app\common;

class Result
{
    // 状态码
    const SUCCESS                       = 0;
    const ERROR                         = 1;
    const NO_USER_INFO                  = 2;
    const NO_ACCESS                     = 3;
    const FORBIDDEN_IP                  = 4;
    const NOT_LOGIN                     = 5;
    const OBJECT_EXIST                  = 6;
    const FORBIDDEN_USER                = 7;
    const OLD_PASSWORLD_ERROR           = 8;
    const SEND_CODE_SUCCESS             = 9;
    const SEND_CODE_ERROR               = 10;
    const EMAIL_ERROR                   = 11;
    const RETRIEVE_PASSWORLD_FAIL       = 12;
    const RETRIEVE_PASSWORLD_SUCCESS    = 13;
    const CODE_ERROR                    = 14;
    const UPLOAD_ERROR                  = 15;

    // 对应结果
    public static $resultMsg = [
        self::SUCCESS                   => '请求成功！',
        self::ERROR                     => '请求错误！',
        self::NO_USER_INFO              => '数据库没有您的用户信息！',
        self::NO_ACCESS                 => '您无权限查看!',
        self::NOT_LOGIN                 => '您还未登录！',
        self::OBJECT_EXIST              => '对象已存在！',
        self::FORBIDDEN_USER            => '您的账户已禁用！',
        self::OLD_PASSWORLD_ERROR       => '原密码输入错误',
        self::SEND_CODE_SUCCESS         => '验证码发送成功',
        self::SEND_CODE_ERROR           => '验证码发送失败',
        self::EMAIL_ERROR               => '邮箱地址错误',
        self::RETRIEVE_PASSWORLD_FAIL   => '密码找回失败',
        self::RETRIEVE_PASSWORLD_SUCCESS=> '密码找回成功',
        self::CODE_ERROR                => '验证码错误',
        self::UPLOAD_ERROR              => '上传文件不存在或有多个文件',
    ];

    // 返回结果
    public static function returnResult($code, $data = [], $count = 0) {
        $result = [
            'code' => $code,
            'msg' => self::$resultMsg[$code],
            'count' => $count,
            'data' => $data,
            'time' => $_SERVER['REQUEST_TIME']
        ];

        return $result;
    }
}