<?php
/**
 * Created by PhpStorm.
 * User: TZX
 * Date: 2020/9/28
 * Time: 10:50
 */

namespace app\common\util;

/**
 * Class dateUtil
 * @package app\common\util
 * 日期工具类
 */
class dateUtil
{
    /**
     * 获取月份第一天和最后一天日期
     * @param int $offset 月份偏移量
     * @param string $date 指定日期
     * @return array
     */
    public static function getMonthFirstAndLast($offset = 0, $date = ''){
        if($date == '') {
            $date = date('Y-m-d',time());
        }

        $first = date('Y-m-01', strtotime("$date +$offset months"));
        $last = date('Y-m-d',strtotime("$first +1 month -1 day"));

        return [
            'first' => $first,
            'last' => $last
        ];
    }
}