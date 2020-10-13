<?php
/**
 * Created by PhpStorm.
 * User: TZX
 * Date: 2020/10/13
 * Time: 10:24
 */

namespace app\common\util;

/**
 * 数组和字符串工具类
 * Class ArrayAndStringUtil
 * @package app\common\util
 */
class ArrayAndStringUtil
{
    // 得到传入数组元素的10位字符串数组
    public static function getTenString($array) {

        $stringArray = [];
        for($i = 1;; $i ++) {
            if($i * 10 > count($array)) {
                array_push($stringArray, implode(',', array_slice($array, $i * 10 - 10, count($array))));
                break;
            } else {
                array_push($stringArray, implode(',', array_slice($array, $i * 10 - 10, $i * 10)));
            }
        }

        return $stringArray;
    }
}