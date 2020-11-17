<?php

namespace app\index\model;

use think\Model;

class TableItemProcess extends Model
{
    // 关联附件（一对多）
    public function attachments()
    {
        return $this->hasMany('Attachment', 'related_id', 'process_id')->where('attachment_type', 'item')->field('source_name,save_path');
    }
}
