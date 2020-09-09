<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/9/9 0009
 * Time: 上午 9:26
 */

namespace app\common\util;


class EncryptionUtil
{
    public static function Md5Encryption($data,$salt){
        $salt = md5($salt);
        $data = md5($data) . $salt;
        return md5($data);
    }
}