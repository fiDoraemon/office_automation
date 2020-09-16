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
        0 => '普通会议',
        1 => '设计评审',
        2 => '阶段准出评审',
        3 => 'ECR评审'
    ];

}