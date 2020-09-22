<?php
/**
 * Created by PhpStorm.
 * User: Link
 * Date: 2020/9/16
 * Time: 17:17
 */

namespace app\index\model;


use think\Model;

class Minute extends Model
{
    protected $pk = 'minute_id';

    /**
     * 与应到人员会议表一对多关联
     * @return \think\model\relation\HasMany
     */
    public function minuteAttends()
    {
        //hasMany('关联模型名','外键名','主键名',['模型别名定义']);
        return $this->hasMany('MinuteAttend','minute_id','minute_id')->field('user_id');
    }

    /**
     * 与会议发起人一对一关联
     * @return \think\model\relation\HasOne
     */
    public function user(){
        return $this->hasOne('User',"user_id","host_id")->field('user_id,user_name');
    }

    /**
     * 与会议类型一对一对应
     * @return \think\model\relation\HasOne
     */
    public function minuteType(){
        return $this->hasOne('MinuteType',"type_id","minute_type")->field('type_id,type_name');
    }

    /**
     * 与会议评审状态一对一对应
     * @return \think\model\relation\HasOne
     */
    public function minuteReview(){
        return $this->hasOne('MinuteReview',"id","review_status")->field('id,review_name');
    }

    /**
     * 与项目状态一对一对应
     * @return \think\model\relation\HasOne
     */
    public function projectStage(){
        return $this->hasOne('ProjectStage',"id","project_stage")->field('id,stage_name');
    }

    /**
     * 与发起会议的所属部门一对一对应
     * @return \think\model\relation\HasOne
     */
    public function department(){
        return $this->hasOne('Department',"department_id","department_id")->field('department_name');
    }



}