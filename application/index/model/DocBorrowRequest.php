<?php
/**
 * Created by PhpStorm.
 * User: Link
 * Date: 2020/11/20
 * Time: 11:26
 */

namespace app\index\model;


use think\Model;

class DocBorrowRequest extends Model
{
    /**
     * 与文档文件一对一对应
     * @return \think\model\relation\HasOne
     */
    public function docFile(){
        return $this -> hasOne('DocFile',"file_id","file_id")
                      -> field("file_code");
    }

    /**
     * 与申请人一对一对应
     * @return \think\model\relation\HasOne
     */
    public function user(){
        return $this -> hasOne('User',"user_id","applicant_id")
                      -> field("user_name");
    }
}