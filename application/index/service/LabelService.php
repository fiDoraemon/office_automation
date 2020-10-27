<?php
/**
 * Created by PhpStorm.
 * User: TZX
 * Date: 2020/10/26
 * Time: 15:54
 */

namespace app\index\service;

use app\index\model\Label;
use app\index\model\MissionLabel;

/**
 * 标签服务类
 * Class LabelService
 * @package app\index\service
 */
class LabelService
{
    // 获取标签列表
    public static function getLabelList() {
        $label = new Label();
        $labelList = $label->field('label_id, label_name')->order('label_id desc')->limit(50)->select();

        return $labelList;
    }

    // 获取任务标签列表
    public static function getMissionLabelList($missionId) {
        $labelList = [];
        $missionLabel = new MissionLabel();

        $missionLabels = $missionLabel->where('mission_id', $missionId)->select();
        foreach ($missionLabels as $missionLabel) {
            array_push($labelList, $missionLabel->label->label_name);
        }

        return implode('；', $labelList);
    }
}