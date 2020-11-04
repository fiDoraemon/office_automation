<?php
/**
 * Created by PhpStorm.
 * User: Link
 * Date: 2020/11/3
 * Time: 17:47
 */

namespace app\index\model;


use think\Model;

class Iqc extends Model
{
    /**
     * 与提交缺陷的员工一对一对应
     * @return \think\model\relation\HasOne
     */
    public function proposer(){
        return $this->hasOne('User',"user_id","proposer_id")->field('user_name');
    }

    public function material(){
        return $this->hasOne('IqcMaterial',"material_code","code")->field('material_name');
    }
}