<?php
/**
 * Created by PhpStorm.
 * User: TZX
 * Date: 2020/10/12
 * Time: 15:22
 */

namespace app\common\util;

/**
 * 接口工具类
 * Class curlUtil
 * @package app\common\util
 */
class curlUtil
{
    /**
     * 调用接口
     * @param $url
     * @param array $data
     * @return mixed
     */
    public static function post($url, $data = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        $output = curl_exec($ch);

        return json_decode($output);
    }
}