<?php
///**
// * Created by PhpStorm.
// * User: Link
// * Date: 2020/9/24
// * Time: 10:08
// */
//
//namespace app\index\model;
//
//
//use think\Model;
//
//class MinuteMission extends Model
//{
//    protected $pk = 'id';
//
//    /**
//     * 与任务一对一对应
//     */
//    public function mission(){
//        return $this->hasOne('Mission',"mission_id","mission_id")->field('mission_id,mission_title,assignee_id,finish_date,status');
//    }
//
//
//}