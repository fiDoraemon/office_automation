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
    const TOKEN_LOGIN_SUCCESS           = 16;
    const USER_ADD_SUCCESS              = 17;
    const USER_ADD_ERROR                = 18;
    const USER_DELETE_SUCCESS           = 19;
    const USER_DELETE_ERROR             = 20;
    const USER_SELECT_SUCCESS           = 21;
    const USER_SELECT_ERROR             = 22;
    const DELETE_ATTACHMENT             = 23;
    const FORBID_DELETE_ROOT            = 24;
    const PARENT_EXIST                  = 25;
    const NOT_MINUTE_TEMP               = 26;
    const NOT_MODIFY_PERMISSION         = 27;
    const NOT_TEMP                      = 28;
    const NOT_ROOT_MISSION              = 29;
    const LACK_REQUIRED_PARAM           = 30;
    const EXIST_USER                    = 31;
    const OBJECT_NOT_EXIST              = 32;
    const PIC_UPLOAD_FAIL               = 33;
    const HAVE_NO_ROOT                  = 34;
    const EXIST_BORROW                  = 35;
    const WAITING_DOC_ADMIN             = 36;
    const LIBRARY_BORROW_FAIL           = 37;
    const LIBRARY_SENDBACK_FAIL         = 38;

    // 对应结果
    public static $resultMsg = [
        self::SUCCESS                   => '请求成功',
        self::ERROR                     => '请求错误',
        self::NO_USER_INFO              => '数据库没有您的用户信息',
        self::NO_ACCESS                 => '没有权限查看',
        self::NOT_LOGIN                 => '您还未登录',
        self::OBJECT_EXIST              => '对象已存在',
        self::FORBIDDEN_USER            => '您的账户已禁用',
        self::OLD_PASSWORLD_ERROR       => '原密码输入错误',
        self::SEND_CODE_SUCCESS         => '验证码发送成功',
        self::SEND_CODE_ERROR           => '验证码发送失败',
        self::EMAIL_ERROR               => '邮箱地址错误',
        self::RETRIEVE_PASSWORLD_FAIL   => '密码找回失败',
        self::RETRIEVE_PASSWORLD_SUCCESS=> '密码找回成功',
        self::CODE_ERROR                => '验证码错误',
        self::UPLOAD_ERROR              => '上传文件不存在或有多个文件',
        self::TOKEN_LOGIN_SUCCESS       => '使用token登录成功',
        self::USER_ADD_SUCCESS          => '添加用户成功',
        self::USER_ADD_ERROR            => '添加用户失败',
        self::USER_DELETE_SUCCESS       => '删除用户成功',
        self::USER_DELETE_ERROR         => '删除用户失败',
        self::USER_SELECT_SUCCESS       => '查询用户成功',
        self::USER_SELECT_ERROR         => '查询用户失败',
        self::DELETE_ATTACHMENT         => '删除附件失败',
        self::FORBID_DELETE_ROOT        => '不允许直接删除根任务',
        self::PARENT_EXIST              => '该任务已有父任务！',
        self::NOT_MINUTE_TEMP           => '不存在临时会议信息！',
        self::NOT_MODIFY_PERMISSION     => '没有修改权限',
        self::NOT_TEMP                  => '没有临时保存的信息',
        self::NOT_ROOT_MISSION          => '任务不是根任务',
        self::LACK_REQUIRED_PARAM       => '缺少必需参数',
        self::EXIST_USER                => '该用户已经存在',
        self::OBJECT_NOT_EXIST          => '对象不存在',
        self::PIC_UPLOAD_FAIL           => '图片上传失败',
        self::HAVE_NO_ROOT              => '没有根任务',
        self::EXIST_BORROW              => '已经存在借阅信息',
        self::WAITING_DOC_ADMIN         => '等待文控审批中',
        self::LIBRARY_BORROW_FAIL       => '借阅失败,该状态下的图书不可借阅',
        self::LIBRARY_SENDBACK_FAIL     => '归还失败'
    ];

    // 返回结果
    public static function returnResult($code, $data = null, $count = 0) {
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