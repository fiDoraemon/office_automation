<?php

namespace app\index\model;

use think\Model;

class MissionLabel extends Model
{
    // 关联标签
    public function label()
    {
        return $this->hasOne('Label', 'label_id', 'label_id')->field('label_name');
    }
}
