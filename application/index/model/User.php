<?php
/**
 * Created by PhpStorm.
 * User: Conqin
 * Date: 2020/9/7 0007
 * Time: 下午 2:00
 */

namespace app\index\model;


use think\Model;

class User extends Model
{
    protected $pk = 'id';

    /**
     * 与所属的部门一对一对应
     * @return \think\model\relation\HasOne
     */
    public function department(){
        return $this->hasOne('Department',"department_id","department_id")->field('department_name');
    }
}