<?php
/**
 * Created by PhpStorm.
 * User: Link
 * Date: 2020/11/20
 * Time: 11:26
 */

namespace app\index\model;


use think\Model;

class DocBorrow extends Model
{
    /**
     * 与文档文件一对一对应
     * @return \think\model\relation\HasOne
     */
    public function docFile(){
        return $this -> hasOne('DocFile',"request_id","request_id")
                      -> field("file_code,source_name");
    }

    /**
     * 与申请人一对一对应
     * @return \think\model\relation\HasOne
     */
    public function user(){
        return $this -> hasOne('User',"user_id","user_id")
                      -> field("user_name");
    }
}