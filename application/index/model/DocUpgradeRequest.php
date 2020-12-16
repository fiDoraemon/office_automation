<?php

namespace app\index\model;

use think\Model;

class DocUpgradeRequest extends Model
{
    /*
     * 关联附件（一对一）
     */
    public function attachment(){
        return $this->hasOne('Attachment',"related_id","request_id")->where('attachment_type', 'doc_upgrade')->field('source_name,save_path,file_size');
    }
}
