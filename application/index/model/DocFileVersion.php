<?php

namespace app\index\model;

use think\Model;

class DocFileVersion extends Model
{
    /*
     * 关联附件
     */
    public function attachment(){
        return $this->hasOne('Attachment',"attachment_id","attachment_id")->field('source_name,save_path,file_size');
    }
}
