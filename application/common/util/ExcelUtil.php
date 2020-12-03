<?php
/**
 * Created by PhpStorm.
 * User: TZX
 * Date: 2020/12/1
 * Time: 16:00
 */

namespace app\common\util;


class ExcelUtil
{
    // 读取excel文件
    public static function readExcel($path) {
        // 创建表格对象
//        $path = APP_PATH . '../public/table_1 (5).xls';
        $objPHPExcel = \PHPExcel_IOFactory::load($path);
        $sheet = $objPHPExcel->getSheet(0);            // 获取表格页面
        $result = [];
        $highestRow = $sheet->getHighestRow();            // 取得总行数
        // 获取字母数组
        $letterArray = [];
        for ($i = 0; $i < 12; $i++) {
            $letterArray[$i] = chr(65 + $i);
        }
        // 从第1列开始获取信息
        for ($i = 1; $i <= $highestRow; $i++) {
            for ($j = 0; $j < 12; $j++) {
                $value = $sheet->getCell("$letterArray[$j]$i")->getValue();
                $value = $value? $value : '';           // null 转为 ''
                $result[$i - 1][$j] = $value;
            }
        }

        return $result;
    }
}