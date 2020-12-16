<?php
/**
 * Created by PhpStorm.
 * User: Link
 * Date: 2020/10/26
 * Time: 17:12
 */

namespace app\index\model;


use think\Model;

class DocFile extends Model
{
    /**
     * 与审批申请一对一关联
     * @return \think\model\relation\HasOne
     */
    public function request(){
        return $this -> hasOne('DocRequest',"request_id","request_id") -> field('applicant_id,project_id,project_stage,description,controlled');
    }
}