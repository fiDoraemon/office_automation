<?php

namespace app\index\model;

use think\Model;

class Attachment extends Model
{
    // 关联用户（一对一）
    public function uploader()
    {
        return $this->hasOne('app\common\model\User','user_id', 'uploader_id')->field('user_name');
    }
}
