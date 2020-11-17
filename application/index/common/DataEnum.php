<?php
/**
 * Created by PhpStorm.
 * User: TZX
 * Date: 2020/9/15
 * Time: 10:59
 */

namespace app\index\common;

// 数据状态信息
class DataEnum
{
    // 任务状态
    public static $missionStatus = [
        0 => '未开始',
        1 => '处理中',
        2 => '已完成',
        3 => '已停用'
    ];

    // 会议类型
    public static $minuteType = [
        1 => '普通会议',
        2 => '设计评审',
        3 => '阶段准出评审',
        4 => 'ECR评审'
    ];

    // 附件类型
    public static $attachmentType = [
        0 => 'mission',         // 任务
        1 => 'minute'           // 会议

    ];

    // 文件上传目录
    const uploadDir = '/office_automation/public/upload/';

    // 钉钉消息输入数据格式
    public static $msgData = [
        'userList' => '',
        'data' => [
            'type' => 'text',
            'content' => ''
        ]
    ];

    // 工作表字段类型
    public static $fieldType = ['text', 'textArea', 'select', 'user', 'users'];
}