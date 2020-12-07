<?php
/**
 * Created by PhpStorm.
 * User: TZX
 * Date: 2020/12/1
 * Time: 16:00
 */

namespace app\common\util;

/**
 * 表格工具类
 * Class ExcelUtil
 * @package app\common\util
 */
class ExcelUtil
{
    /**
     * 读取excel文件
     * @param $path
     * @param $cols
     * @return array
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     */
    public static function readExcel($path, $cols) {
        // 创建表格对象
        $objPHPExcel = \PHPExcel_IOFactory::load($path);
        $sheet = $objPHPExcel->getSheet(0);            // 获取表格页面
        $result = [];
        $highestRow = $sheet->getHighestRow();            // 取得总行数
        // 获取字母数组
        $letterArray = [];
        for ($i = 0; $i < $cols; $i++) {
            $letterArray[$i] = chr(65 + $i);
        }
        // 从第1列开始获取信息
        for ($i = 1; $i <= $highestRow; $i++) {
            for ($j = 0; $j < $cols; $j++) {
                $value = $sheet->getCell("$letterArray[$j]$i")->getValue();
                $value = $value? $value : '';           // null 转为 ''
                $result[$i - 1][$j] = $value;
            }
        }

        return $result;
    }
}