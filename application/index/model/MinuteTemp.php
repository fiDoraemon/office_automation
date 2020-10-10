<?php
/**
 * Created by PhpStorm.
 * User: Link
 * Date: 2020/9/30
 * Time: 16:20
 */

namespace app\index\model;


use think\Model;

class MinuteTemp extends Model
{
    /**
     * 与应到人员会议表一对多关联
     * @return \think\model\relation\HasMany
     */
    public function minuteAttends()
    {
        return $this->hasMany('MinuteAttendTemp','minute_id','id')->field('user_id');
    }
}