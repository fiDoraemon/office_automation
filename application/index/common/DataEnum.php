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
}